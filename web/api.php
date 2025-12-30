<?php

use Llama\Llama;
use Llama\Chat;
use Llama\Embedding;
use Llama\Reranker;
use Llama\Templates\Qwen3Template;
use Llama\Schema\JsonSchemaBuilder;
use Llama\Exception\LlamaException;
use Llama\Exception\ValidationException;
use Llama\Exception\TimeoutException;
use Llama\Exception\ProcessException;
use Llama\Exception\EmbeddingException;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

// ============================================================================
// CONFIGURATION & DETECTION (same as index.php)
// ============================================================================

$modelDir = __DIR__ . '/../models';
$models = glob($modelDir . '/*.gguf');
$modelPath = $models[0] ?? null;

// Try to find an embedding-specific model
$embeddingModelPath = null;
foreach ($models as $model) {
    if (stripos(basename($model), 'embedding') !== false) {
        $embeddingModelPath = $model;
        break;
    }
}
// Fallback to the first model if no embedding-specific model found
if (!$embeddingModelPath && $modelPath) {
    $embeddingModelPath = $modelPath;
}

// Try to find a reranker-specific model
$rerankerModelPath = null;
foreach ($models as $model) {
    if (stripos(basename($model), 'rerank') !== false) {
        $rerankerModelPath = $model;
        break;
    }
}
// Fallback to embedding model if no reranker-specific model found
if (!$rerankerModelPath && $embeddingModelPath) {
    $rerankerModelPath = $embeddingModelPath;
}

// Try multiple paths for the binary
$possibleBinaries = [
    __DIR__ . '/../llama.cpp/build/bin/llama-cli', // CMake build path
    __DIR__ . '/../llama.cpp/llama-cli',           // Legacy make path
    __DIR__ . '/../llama.cpp/main',                // Old compiled name
    '/usr/local/bin/llama-cli',                // System install
    '/usr/bin/llama-cli',
    getenv('HOME') . '/.local/bin/llama-cli',  // User local bin
];

$binaryPath = null;
foreach ($possibleBinaries as $bin) {
    if (file_exists($bin) && is_executable($bin)) {
        $binaryPath = realpath($bin);
        break;
    }
}

// Fallback: Check if it's in PATH
if (!$binaryPath) {
    $which = trim(shell_exec('which llama-cli'));
    if (!empty($which) && is_executable($which)) {
        $binaryPath = $which;
    } else {
        $binaryPath = 'llama-cli';
    }
}

$error = null;
$response = ['success' => false, 'error' => 'Unknown action'];

// Initialize Chat History if not exists
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [
        ['role' => 'system', 'content' => 'You are a helpful AI assistant powered by LlamaPHP.']
    ];
}

// Get action from query string or POST
$action = $_GET['action'] ?? ($_POST['action'] ?? 'test');

// Helper function to send JSON response
function sendJson($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Helper function for streaming (Server-Sent Events)
function sendStreamEvent($data) {
    echo "data: " . json_encode($data) . "\n\n";
    flush();
    if (ob_get_level()) ob_flush();
}

// Helper function to validate required parameters
function validateRequired($params, $required) {
    foreach ($required as $param) {
        if (!isset($params[$param]) || trim($params[$param]) === '') {
            throw new ValidationException("Missing required parameter: $param");
        }
    }
}

try {
    // Check if model exists
    if (!$modelPath) {
        throw new ValidationException("No GGUF model found in models/ directory.");
    }

    // Check if binary exists and is executable
    if (!file_exists($binaryPath) || !is_executable($binaryPath)) {
        throw new ValidationException("Llama binary not found or not executable: $binaryPath");
    }

    switch ($action) {
        case 'test':
            // Simple API test
            $response = [
                'success' => true,
                'message' => 'LlamaPHP API is working',
                'model' => basename($modelPath),
                'binary' => $binaryPath,
                'timestamp' => date('c')
            ];
            sendJson($response);
            break;

        case 'reset':
            // Reset chat history
            $_SESSION['messages'] = [
                ['role' => 'system', 'content' => 'You are a helpful AI assistant powered by LlamaPHP.']
            ];
            $response = [
                'success' => true,
                'message' => 'Chat history reset'
            ];
            sendJson($response);
            break;

        case 'history':
            // Return current chat history
            $response = [
                'success' => true,
                'messages' => $_SESSION['messages'] ?? []
            ];
            sendJson($response);
            break;

        case 'chat':
            // Handle chat request
            validateRequired($_POST, ['message']);
            
            $userMsg = trim($_POST['message']);
            $_SESSION['messages'][] = ['role' => 'user', 'content' => $userMsg];

            $template = null;
            $isQwen3 = str_contains(strtolower(basename($modelPath)), 'qwen3');
            if ($isQwen3) {
                $template = new Qwen3Template();
                if (isset($_POST['thinking']) && $_POST['thinking']) {
                    $template->setThinkingMode(true);
                }
            }

            $chat = new Chat($binaryPath, $modelPath, $template);
            
            $options = [
                'ctx_size' => (int) ($_POST['ctx_size'] ?? 2048),
                'max_tokens' => (int) ($_POST['max_tokens'] ?? 512),
                'temperature' => (float) ($_POST['temperature'] ?? 0.6),
                'repeat_penalty' => (float) ($_POST['repeat_penalty'] ?? 1.2),
                'timeout' => (int) ($_POST['timeout'] ?? 60),
                // Control thinking/reasoning
                'reasoning_budget' => isset($_POST['thinking']) && $_POST['thinking'] ? -1 : 0,
            ];
            // Set reasoning format for thinking mode (required for proper thinking output)
            if (isset($_POST['thinking']) && $_POST['thinking']) {
                $options['reasoning_format'] = 'deepseek';
            }

            // JSON Schema mode
            if (isset($_POST['json_mode']) && $_POST['json_mode']) {
                $schema = JsonSchemaBuilder::build(
                    JsonSchemaBuilder::object([
                        'title' => 'string',
                        'year' => 'integer',
                        'genre' => JsonSchemaBuilder::list('string'),
                        'summary' => 'string'
                    ])
                );
                $options['json_schema'] = $schema;
                $options['temperature'] = 0.1;
                // Append instruction to user message
                $userMsg .= " (Respond in JSON format describing a movie)";
                $_SESSION['messages'][count($_SESSION['messages']) - 1]['content'] = $userMsg;
            }

            $startTime = microtime(true);
            $assistantResponse = $chat->chat($_SESSION['messages'], $options);
            $endTime = microtime(true);

            // Parse thinking output for Qwen3 models (always parse to extract thinking tags if present)
            if ($template instanceof Qwen3Template) {
                $parsed = $template->parseThinkingOutput($assistantResponse);
                $finalResponse = $parsed['response'];
                $thinkingContent = $parsed['thinking'];

                // If thinking mode was explicitly requested, store raw response with thinking tags
                // Otherwise, store only the final response (without thinking content)
                if (isset($_POST['thinking']) && $_POST['thinking']) {
                    $_SESSION['messages'][] = ['role' => 'assistant', 'content' => $assistantResponse];
                } else {
                    $_SESSION['messages'][] = ['role' => 'assistant', 'content' => $finalResponse];
                }
            } else {
                $_SESSION['messages'][] = ['role' => 'assistant', 'content' => $assistantResponse];
            }

            $response = [
                'success' => true,
                'time' => round($endTime - $startTime, 2),
                'message' => 'Response generated successfully'
            ];
            sendJson($response);
            break;

        case 'generate':
            // Handle text generation (non-streaming)
            validateRequired($_POST, ['prompt']);
            
            $prompt = trim($_POST['prompt']);
            $llama = new Llama($binaryPath, $modelPath);
            
            $options = [
                'max_tokens' => (int) ($_POST['max_tokens'] ?? 256),
                'temperature' => (float) ($_POST['temperature'] ?? 0.8),
                'top_p' => (float) ($_POST['top_p'] ?? 0.9),
                'repeat_penalty' => (float) ($_POST['repeat_penalty'] ?? 1.1),
                'ctx_size' => (int) ($_POST['ctx_size'] ?? 512),
                'timeout' => (int) ($_POST['timeout'] ?? 60),
                'reasoning_budget' => isset($_POST['thinking']) && $_POST['thinking'] ? -1 : 0,
            ];
            if (isset($_POST['thinking']) && $_POST['thinking']) {
                $options['reasoning_format'] = 'deepseek';
            }

            $startTime = microtime(true);
            $output = $llama->generate($prompt, $options);
            $endTime = microtime(true);

            $response = [
                'success' => true,
                'output' => $output,
                'time' => round($endTime - $startTime, 2),
                'prompt_length' => strlen($prompt),
                'output_length' => strlen($output)
            ];
            sendJson($response);
            break;

        case 'stream':
            // Handle streaming generation
            validateRequired($_POST, ['prompt']);
            
            $prompt = trim($_POST['prompt']);
            $llama = new Llama($binaryPath, $modelPath);
            
            $options = [
                'max_tokens' => (int) ($_POST['max_tokens'] ?? 512),
                'temperature' => (float) ($_POST['temperature'] ?? 0.7),
                'top_p' => (float) ($_POST['top_p'] ?? 0.9),
                'repeat_penalty' => (float) ($_POST['repeat_penalty'] ?? 1.1),
                'ctx_size' => (int) ($_POST['ctx_size'] ?? 512),
                'timeout' => (int) ($_POST['timeout'] ?? 120), // Longer timeout for streaming
                'reasoning_budget' => isset($_POST['thinking']) && $_POST['thinking'] ? -1 : 0,
            ];
            if (isset($_POST['thinking']) && $_POST['thinking']) {
                $options['reasoning_format'] = 'deepseek';
            }

            // Set headers for Server-Sent Events
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no'); // Disable nginx buffering
            
            // Send initial connection event
            sendStreamEvent(['status' => 'connected', 'time' => date('c')]);
            
            // Check if transport supports streaming
            $transport = null;
            $reflection = new ReflectionClass($llama);
            $transportProperty = $reflection->getProperty('transport');
            $transportProperty->setAccessible(true);
            $transport = $transportProperty->getValue($llama);
            
            if (!method_exists($transport, 'generateStream')) {
                sendStreamEvent(['error' => 'Streaming not supported by current transport']);
                sendStreamEvent(['token' => '[DONE]']);
                exit;
            }

            $tokenCount = 0;
            $startTime = microtime(true);
            
            try {
                foreach ($transport->generateStream($prompt, $options) as $chunk) {
                    $tokenCount++;
                    sendStreamEvent(['token' => $chunk, 'count' => $tokenCount]);
                    
                    // Small delay to make streaming visible (optional)
                    usleep(50000); // 50ms
                }
            } catch (Exception $e) {
                sendStreamEvent(['error' => $e->getMessage()]);
            }
            
            $endTime = microtime(true);
            
            // Send final statistics
            sendStreamEvent([
                'stats' => [
                    'tokens' => $tokenCount,
                    'time' => round($endTime - $startTime, 2),
                    'tokens_per_second' => $tokenCount > 0 ? round($tokenCount / ($endTime - $startTime), 2) : 0
                ]
            ]);
            
            sendStreamEvent(['token' => '[DONE]']);
            break;

        case 'embed':
            // Handle embedding generation and similarity calculation
            validateRequired($_POST, ['text1', 'text2']);
            
            $text1 = trim($_POST['text1']);
            $text2 = trim($_POST['text2']);
            
            $embedding = new Embedding($binaryPath, $embeddingModelPath);
            
            $options = [
                'timeout' => (int) ($_POST['timeout'] ?? 60),
                'ctx_size' => (int) ($_POST['ctx_size'] ?? 512),
            ];
            // Only add threads if explicitly provided and positive
            if (isset($_POST['threads']) && (int)$_POST['threads'] > 0) {
                $options['threads'] = (int)$_POST['threads'];
            }

            $startTime = microtime(true);
            $vector1 = $embedding->embed($text1, $options);
            $vector2 = $embedding->embed($text2, $options);
            $endTime = microtime(true);

            // Calculate cosine similarity
            $dot = 0.0;
            $norm1 = 0.0;
            $norm2 = 0.0;
            $dimension = count($vector1);
            
            for ($i = 0; $i < $dimension; $i++) {
                $dot += $vector1[$i] * $vector2[$i];
                $norm1 += $vector1[$i] * $vector1[$i];
                $norm2 += $vector2[$i] * $vector2[$i];
            }
            
            $similarity = 0.0;
            if ($norm1 > 0 && $norm2 > 0) {
                $similarity = $dot / (sqrt($norm1) * sqrt($norm2));
            }

            $response = [
                'success' => true,
                'embedding1' => $vector1,
                'embedding2' => $vector2,
                'similarity' => $similarity,
                'time' => round($endTime - $startTime, 2),
                'dimension' => $dimension
            ];
            sendJson($response);
            break;

        case 'rerank':
            // Handle reranking score calculation
            validateRequired($_POST, ['query', 'document']);

            $query = trim($_POST['query']);
            $document = trim($_POST['document']);

            $reranker = new Reranker($binaryPath, $rerankerModelPath);

            $options = [
                'timeout' => (int) ($_POST['timeout'] ?? 60),
                'ctx_size' => (int) ($_POST['ctx_size'] ?? 512),
            ];
            // Only add threads if explicitly provided and positive
            if (isset($_POST['threads']) && (int)$_POST['threads'] > 0) {
                $options['threads'] = (int)$_POST['threads'];
            }
            // Custom separator if provided
            if (isset($_POST['cls_separator']) && is_string($_POST['cls_separator'])) {
                $options['cls_separator'] = $_POST['cls_separator'];
            }

            $startTime = microtime(true);
            $score = $reranker->rerank($query, $document, $options);
            $endTime = microtime(true);

            // Ensure score is a float (if array, take first)
            if (is_array($score)) {
                $score = $score[0] ?? 0.0;
            }

            $response = [
                'success' => true,
                'query' => $query,
                'document' => $document,
                'score' => $score,
                'time' => round($endTime - $startTime, 2)
            ];
            sendJson($response);
            break;

        default:
            throw new ValidationException("Unknown action: $action");
    }

} catch (ValidationException $e) {
    $response = ['success' => false, 'error' => 'Validation error: ' . $e->getMessage()];
    sendJson($response);
} catch (TimeoutException $e) {
    $response = ['success' => false, 'error' => 'Timeout: ' . $e->getMessage()];
    sendJson($response);
} catch (ProcessException $e) {
    $response = ['success' => false, 'error' => 'Process error: ' . $e->getMessage()];
    sendJson($response);
} catch (EmbeddingException $e) {
    $response = ['success' => false, 'error' => 'Embedding error: ' . $e->getMessage()];
    sendJson($response);
} catch (LlamaException $e) {
    $response = ['success' => false, 'error' => 'Llama error: ' . $e->getMessage()];
    sendJson($response);
} catch (Exception $e) {
    $response = ['success' => false, 'error' => 'Unexpected error: ' . $e->getMessage()];
    sendJson($response);
}