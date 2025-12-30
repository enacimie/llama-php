<?php

use Llama\Llama;
use Llama\Chat;
use Llama\Embedding;
use Llama\Templates\Qwen3Template;
use Llama\Schema\JsonSchemaBuilder;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

// ============================================================================
// CONFIGURATION & DETECTION
// ============================================================================

$modelDir = __DIR__ . '/../models';
$models = glob($modelDir . '/*.gguf');
$modelPath = $models[0] ?? null;

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
$success = null;

if (!$modelPath) {
    $error = "No GGUF model found in <code>models/</code> directory. Please download a model to continue.";
}

// Initialize Chat History
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [
        ['role' => 'system', 'content' => 'You are a helpful AI assistant powered by LlamaPHP.']
    ];
}

// Handle Reset (traditional form submit)
if (isset($_POST['reset'])) {
    $_SESSION['messages'] = [
        ['role' => 'system', 'content' => 'You are a helpful AI assistant powered by LlamaPHP.']
    ];
    $success = "Chat history has been reset.";
}

// Handle traditional form submission (fallback, but primary interaction is via API)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $modelPath && !isset($_POST['api'])) {
    $userMsg = trim($_POST['message']);
    if (!empty($userMsg)) {
        $_SESSION['messages'][] = ['role' => 'user', 'content' => $userMsg];
        try {
            $template = null;
            if (str_contains(strtolower(basename($modelPath)), 'qwen3')) {
                $template = new Qwen3Template();
                if (isset($_POST['thinking'])) {
                    $template->setThinkingMode(true);
                }
            }
            $chat = new Chat($binaryPath, $modelPath, $template);
            $options = [
                'ctx_size' => 2048,
                'max_tokens' => 512,
                'temperature' => 0.6,
                'repeat_penalty' => 1.2
            ];
            if (isset($_POST['json_mode'])) {
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
                $userMsg .= " (Respond in JSON format describing a movie)";
                $_SESSION['messages'][count($_SESSION['messages']) - 1]['content'] = $userMsg;
            }
            $response = $chat->chat($_SESSION['messages'], $options);
            // Parse thinking output for Qwen3 models (always parse to extract thinking tags if present)
            if ($template instanceof Qwen3Template) {
                $parsed = $template->parseThinkingOutput($response);
                $finalResponse = $parsed['response'];
                $thinkingContent = $parsed['thinking'];

                // If thinking mode was explicitly requested, store raw response with thinking tags
                // Otherwise, store only the final response (without thinking content)
                if (isset($_POST['thinking']) && $_POST['thinking']) {
                    $_SESSION['messages'][] = ['role' => 'assistant', 'content' => $response];
                } else {
                    $_SESSION['messages'][] = ['role' => 'assistant', 'content' => $finalResponse];
                }
            } else {
                $_SESSION['messages'][] = ['role' => 'assistant', 'content' => $response];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$modelName = $modelPath ? basename($modelPath) : 'No model found';
$binaryName = $binaryPath ? basename($binaryPath) : 'llama-cli';
$isQwen3 = $modelPath && str_contains(strtolower(basename($modelPath)), 'qwen3');

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LlamaPHP Demo</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Material Design inspired custom CSS -->
    <style>
        :root {
            --primary-color: #6750a4;
            --secondary-color: #625b71;
            --surface-color: #fef7ff;
            --background-color: #fef7ff;
            --error-color: #ba1a1a;
            --success-color: #006e03;
        }
        body {
            background-color: var(--background-color);
            font-family: 'Roboto', 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .navbar-brand {
            font-weight: 500;
            color: var(--primary-color) !important;
        }
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            transition: box-shadow 0.3s ease;
            background-color: var(--surface-color);
        }
        .card:hover {
            box-shadow: 0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 20px;
            padding: 8px 24px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #5a4a8f;
            border-color: #5a4a8f;
        }
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 20px;
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .tab-pane {
            padding-top: 20px;
        }
        .message-bubble {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            margin-bottom: 12px;
            position: relative;
        }
        .message-user {
            background-color: var(--primary-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        .message-assistant {
            background-color: #e8def8;
            color: #1d192b;
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }
        .message-system {
            background-color: #e6e1e5;
            color: #49454f;
            font-size: 0.9em;
            text-align: center;
            border-radius: 12px;
            max-width: 100%;
        }
        .chat-container {
            height: 500px;
            overflow-y: auto;
            padding: 20px;
            background-color: #f7f2fa;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .streaming-output {
            font-family: 'Courier New', monospace;
            background-color: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            min-height: 200px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .embedding-vector {
            font-family: 'Courier New', monospace;
            font-size: 0.8em;
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            max-height: 200px;
        }
        .loading-spinner {
            display: none;
        }
        .alert {
            border-radius: 12px;
            border: none;
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #cac4d0;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(103, 80, 164, 0.15);
        }
        .nav-tabs {
            border-bottom: 2px solid #e6e1e5;
        }
        .nav-tabs .nav-link {
            border: none;
            border-radius: 12px 12px 0 0;
            margin-right: 8px;
            color: var(--secondary-color);
            font-weight: 500;
        }
        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            background-color: #f3edf7;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 3px solid var(--primary-color);
        }
        .status-badge {
            background-color: #e8def8;
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .thinking-content {
            background-color: #fff8e1;
            border-left: 4px solid #ffb300;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 0 8px 8px 0;
            font-size: 0.9em;
            color: #5d4037;
        }
        .progress-bar {
            background-color: var(--primary-color);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-robot me-2"></i>
                <span class="fw-bold">LlamaPHP</span> Demo
            </a>
            <div class="d-flex align-items-center">
                <span class="status-badge me-3">
                    <i class="bi bi-cpu me-1"></i> <?= htmlspecialchars($binaryName) ?>
                </span>
                <span class="status-badge">
                    <i class="bi bi-file-earmark-binary me-1"></i> <?= htmlspecialchars($modelName) ?>
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="demoTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="chat-tab" data-bs-toggle="tab" data-bs-target="#chat" type="button" role="tab">
                            <i class="bi bi-chat-dots me-2"></i>Chat
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate" type="button" role="tab">
                            <i class="bi bi-text-paragraph me-2"></i>Generate
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stream-tab" data-bs-toggle="tab" data-bs-target="#stream" type="button" role="tab">
                            <i class="bi bi-lightning-charge me-2"></i>Streaming
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="embed-tab" data-bs-toggle="tab" data-bs-target="#embed" type="button" role="tab">
                            <i class="bi bi-vector-pen me-2"></i>Embeddings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button" role="tab">
                            <i class="bi bi-gear me-2"></i>Configuration
                        </button>
                    </li>
                </ul>

                <!-- Tab Contents -->
                <div class="tab-content p-4 card" id="demoTabsContent">
                    
                    <!-- CHAT TAB -->
                    <div class="tab-pane fade show active" id="chat" role="tabpanel">
                        <h4 class="mb-3">Interactive Chat</h4>
                        <div class="chat-container" id="chatContainer">
                            <?php foreach ($_SESSION['messages'] as $msg): ?>
                                <div class="message-bubble <?= 'message-' . $msg['role'] ?>">
                                    <strong><?= ucfirst($msg['role']) ?>:</strong>
                                    <?php
                                        $content = htmlspecialchars($msg['content']);
                                        // Format thinking tags
                                        if (str_contains($content, '&lt;think&gt;') && str_contains($content, '&lt;/think&gt;')) {
                                            $content = preg_replace('/&lt;think&gt;(.*?)&lt;\/think&gt;/s', 
                                                '<div class="thinking-content"><strong>Thinking:</strong><br>$1</div>', 
                                                $content);
                                        }
                                        echo nl2br($content);
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form id="chatForm" method="post" action="api.php?action=chat">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <textarea class="form-control" name="message" id="chatMessage" 
                                              placeholder="Type your message here..." rows="2" required></textarea>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex flex-column h-100">
                                        <div class="mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="thinking" id="thinkingCheck" <?= $isQwen3 ? '' : 'disabled' ?>>
                                                <label class="form-check-label" for="thinkingCheck">
                                                    Thinking Mode <?= $isQwen3 ? '' : '(Qwen3 only)' ?>
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="json_mode" id="jsonCheck">
                                                <label class="form-check-label" for="jsonCheck">
                                                    JSON Schema (Movie)
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mt-auto">
                                            <button type="submit" class="btn btn-primary w-100 mb-2" id="chatSend">
                                                <i class="bi bi-send me-2"></i>Send Message
                                            </button>
                                            <button type="button" class="btn btn-outline-primary w-100" id="chatReset">
                                                <i class="bi bi-arrow-clockwise me-2"></i>Reset Chat
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div class="mt-3">
                            <div class="spinner-border spinner-border-sm text-primary loading-spinner" id="chatSpinner" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="text-muted small" id="chatStatus">Ready</div>
                        </div>
                    </div>

                    <!-- GENERATE TAB -->
                    <div class="tab-pane fade" id="generate" role="tabpanel">
                        <h4 class="mb-3">Text Generation</h4>
                        <form id="generateForm" method="post" action="api.php?action=generate">
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label for="generatePrompt" class="form-label">Prompt</label>
                                    <textarea class="form-control" name="prompt" id="generatePrompt" rows="3" required>Write a short story about a robot learning to paint.</textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="generateMaxTokens" class="form-label">Max Tokens</label>
                                    <input type="number" class="form-control" name="max_tokens" id="generateMaxTokens" value="256" min="1" max="4096">
                                    <label for="generateTemperature" class="form-label mt-2">Temperature</label>
                                    <input type="range" class="form-range" name="temperature" id="generateTemperature" min="0" max="2" step="0.1" value="0.8">
                                    <div class="d-flex justify-content-between">
                                        <small>0.0</small>
                                        <small id="tempValue">0.8</small>
                                        <small>2.0</small>
                                    </div>
                                    <label for="generateTopP" class="form-label mt-2">Top P</label>
                                    <input type="range" class="form-range" name="top_p" id="generateTopP" min="0" max="1" step="0.05" value="0.9">
                                    <div class="d-flex justify-content-between">
                                        <small>0.0</small>
                                        <small id="topPValue">0.9</small>
                                        <small>1.0</small>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" id="generateSend">
                                <i class="bi bi-magic me-2"></i>Generate
                            </button>
                        </form>
                        <div class="mt-4">
                            <h5>Output</h5>
                            <div class="card">
                                <div class="card-body">
                                    <pre id="generateOutput" class="mb-0">Output will appear here...</pre>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="spinner-border spinner-border-sm text-primary loading-spinner" id="generateSpinner" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="text-muted small" id="generateStatus">Ready</div>
                        </div>
                    </div>

                    <!-- STREAMING TAB -->
                    <div class="tab-pane fade" id="stream" role="tabpanel">
                        <h4 class="mb-3">Real-time Streaming</h4>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Streaming shows tokens as they are generated, using the new <code>generateStream()</code> method.
                        </div>
                        <form id="streamForm" method="post" action="api.php?action=stream">
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label for="streamPrompt" class="form-label">Prompt</label>
                                    <textarea class="form-control" name="prompt" id="streamPrompt" rows="3" required>Explain the concept of recursion in programming with a simple example.</textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="streamMaxTokens" class="form-label">Max Tokens</label>
                                    <input type="number" class="form-control" name="max_tokens" id="streamMaxTokens" value="512" min="1" max="4096">
                                    <label for="streamTemperature" class="form-label mt-2">Temperature</label>
                                    <input type="range" class="form-range" name="temperature" id="streamTemperature" min="0" max="2" step="0.1" value="0.7">
                                    <div class="d-flex justify-content-between">
                                        <small>0.0</small>
                                        <small id="streamTempValue">0.7</small>
                                        <small>2.0</small>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary me-2" id="streamStart">
                                <i class="bi bi-play-circle me-2"></i>Start Streaming
                            </button>
                            <button type="button" class="btn btn-danger" id="streamStop" disabled>
                                <i class="bi bi-stop-circle me-2"></i>Stop
                            </button>
                        </form>
                        <div class="mt-4">
                            <h5>Stream Output <span class="badge bg-info" id="streamTokenCount">0 tokens</span></h5>
                            <div class="streaming-output" id="streamOutput">
                                <!-- Tokens will appear here in real-time -->
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="spinner-border spinner-border-sm text-primary loading-spinner" id="streamSpinner" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="text-muted small" id="streamStatus">Ready to stream</div>
                        </div>
                    </div>

                    <!-- EMBEDDINGS TAB -->
                    <div class="tab-pane fade" id="embed" role="tabpanel">
                        <h4 class="mb-3">Text Embeddings</h4>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Note: Your model must support embeddings (look for models with "all-MiniLM", "bge", "e5" in name).
                        </div>
                        <form id="embedForm" method="post" action="api.php?action=embed">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="embedText1" class="form-label">Text 1</label>
                                    <textarea class="form-control" name="text1" id="embedText1" rows="3" required>The cat sits on the mat</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="embedText2" class="form-label">Text 2</label>
                                    <textarea class="form-control" name="text2" id="embedText2" rows="3" required>A feline is resting on a rug</textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3" id="embedSend">
                                <i class="bi bi-calculator me-2"></i>Calculate Embeddings
                            </button>
                        </form>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h5>Embedding 1</h5>
                                <div class="embedding-vector" id="embedOutput1">[]</div>
                                <div class="text-muted small mt-1" id="embedDim1">Dimension: -</div>
                            </div>
                            <div class="col-md-6">
                                <h5>Embedding 2</h5>
                                <div class="embedding-vector" id="embedOutput2">[]</div>
                                <div class="text-muted small mt-1" id="embedDim2">Dimension: -</div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5>Cosine Similarity</h5>
                            <div class="card">
                                <div class="card-body">
                                    <h1 class="display-4 text-center" id="similarityScore">0.00</h1>
                                    <p class="text-center text-muted">Higher values indicate more similar meaning</p>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" id="similarityBar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="spinner-border spinner-border-sm text-primary loading-spinner" id="embedSpinner" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="text-muted small" id="embedStatus">Ready</div>
                        </div>
                    </div>

                    <!-- CONFIGURATION TAB -->
                    <div class="tab-pane fade" id="config" role="tabpanel">
                        <h4 class="mb-3">Configuration & Status</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="bi bi-file-earmark-binary me-2"></i>Model Information
                                    </div>
                                    <div class="card-body">
                                        <?php if ($modelPath): ?>
                                            <h5><?= htmlspecialchars(basename($modelPath)) ?></h5>
                                            <p class="text-muted"><?= htmlspecialchars($modelPath) ?></p>
                                            <p><strong>Size:</strong> <?= filesize($modelPath) > 0 ? number_format(filesize($modelPath) / (1024*1024*1024), 2) . ' GB' : 'Unknown' ?></p>
                                            <p><strong>Last modified:</strong> <?= date('Y-m-d H:i:s', filemtime($modelPath)) ?></p>
                                            <div class="alert alert-success">
                                                <i class="bi bi-check-circle me-2"></i>Model file found and accessible.
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-danger">
                                                <i class="bi bi-exclamation-triangle me-2"></i>No model file found in <code>models/</code> directory.
                                            </div>
                                            <p>Download a GGUF model from Hugging Face and place it in the <code>models/</code> directory.</p>
                                            <a href="https://huggingface.co/models?search=gguf" class="btn btn-outline-primary" target="_blank">
                                                <i class="bi bi-download me-2"></i>Browse Models
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="bi bi-cpu me-2"></i>Llama.cpp Binary
                                    </div>
                                    <div class="card-body">
                                        <h5><?= htmlspecialchars(basename($binaryPath)) ?></h5>
                                        <p class="text-muted"><?= htmlspecialchars($binaryPath) ?></p>
                                        <?php if (file_exists($binaryPath) && is_executable($binaryPath)): ?>
                                            <p><strong>Executable:</strong> Yes</p>
                                            <p><strong>Permissions:</strong> <?= substr(sprintf('%o', fileperms($binaryPath)), -4) ?></p>
                                            <div class="alert alert-success">
                                                <i class="bi bi-check-circle me-2"></i>Binary found and executable.
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle me-2"></i>Binary not found or not executable.
                                            </div>
                                            <p>You need to compile llama.cpp or download the binary.</p>
                                            <a href="https://github.com/ggerganov/llama.cpp" class="btn btn-outline-primary" target="_blank">
                                                <i class="bi bi-github me-2"></i>Compile Instructions
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-code-slash me-2"></i>API Testing
                            </div>
                            <div class="card-body">
                                <p>Test the API endpoints directly:</p>
                                <div class="list-group">
                                    <a href="api.php?action=test" class="list-group-item list-group-item-action" target="_blank">
                                        <i class="bi bi-plug me-2"></i>Test API Connection
                                    </a>
                                    <a href="api.php?action=chat&message=Hello&stream=0" class="list-group-item list-group-item-action" target="_blank">
                                        <i class="bi bi-chat me-2"></i>Test Chat Endpoint
                                    </a>
                                    <a href="api.php?action=generate&prompt=Hello&max_tokens=10" class="list-group-item list-group-item-action" target="_blank">
                                        <i class="bi bi-text-left me-2"></i>Test Generate Endpoint
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- /tab-content -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-3 text-center text-muted border-top">
        <div class="container">
            <p class="mb-0">
                <strong>LlamaPHP</strong> &copy; <?= date('Y') ?> - A robust PHP wrapper for llama.cpp
                <br>
                <small class="text-muted">This demo showcases the improved features: validation, logging, streaming, and cancellation.</small>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for simpler AJAX) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        $(document).ready(function() {
            // Update range values display
            $('#generateTemperature').on('input', function() {
                $('#tempValue').text($(this).val());
            });
            $('#generateTopP').on('input', function() {
                $('#topPValue').text($(this).val());
            });
            $('#streamTemperature').on('input', function() {
                $('#streamTempValue').text($(this).val());
            });

            // Global variables for streaming
            let streamController = null;
            let tokenCount = 0;

            // Chat Form Submission (AJAX)
            $('#chatForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = form.serialize();
                $('#chatSpinner').show();
                $('#chatStatus').text('Generating response...').removeClass('text-danger').addClass('text-info');
                
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Reload the page to show updated chat (simplest approach)
                            location.reload();
                        } else {
                            $('#chatStatus').text('Error: ' + response.error).addClass('text-danger');
                        }
                    },
                    error: function(xhr) {
                        $('#chatStatus').text('Request failed: ' + xhr.statusText).addClass('text-danger');
                    },
                    complete: function() {
                        $('#chatSpinner').hide();
                    }
                });
            });

            // Chat Reset
            $('#chatReset').on('click', function() {
                $.ajax({
                    url: 'api.php?action=reset',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    }
                });
            });

            // Generate Form Submission
            $('#generateForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = form.serialize();
                $('#generateSpinner').show();
                $('#generateStatus').text('Generating...').removeClass('text-danger').addClass('text-info');
                $('#generateOutput').text('Generating...');
                
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#generateOutput').text(response.output);
                            $('#generateStatus').text('Completed in ' + response.time + 's').removeClass('text-info');
                        } else {
                            $('#generateOutput').text('Error: ' + response.error);
                            $('#generateStatus').text('Error').addClass('text-danger');
                        }
                    },
                    error: function(xhr) {
                        $('#generateOutput').text('Request failed: ' + xhr.statusText);
                        $('#generateStatus').text('Request failed').addClass('text-danger');
                    },
                    complete: function() {
                        $('#generateSpinner').hide();
                    }
                });
            });

            // Streaming Form Submission
            $('#streamForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = form.serialize();
                
                // Reset output
                $('#streamOutput').html('');
                tokenCount = 0;
                $('#streamTokenCount').text('0 tokens');
                $('#streamSpinner').show();
                $('#streamStatus').text('Starting stream...').removeClass('text-danger').addClass('text-info');
                $('#streamStart').prop('disabled', true);
                $('#streamStop').prop('disabled', false);
                
                // Create new AbortController for this request
                streamController = new AbortController();
                
                fetch('api.php?action=stream', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData,
                    signal: streamController.signal
                })
                .then(response => {
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();
                    
                    function readStream() {
                        return reader.read().then(({done, value}) => {
                            if (done) {
                                $('#streamSpinner').hide();
                                $('#streamStatus').text('Stream completed').removeClass('text-info');
                                $('#streamStart').prop('disabled', false);
                                $('#streamStop').prop('disabled', true);
                                return;
                            }
                            
                            const chunk = decoder.decode(value);
                            const lines = chunk.split('\n');
                            
                            lines.forEach(line => {
                                if (line.startsWith('data: ')) {
                                    const data = line.substring(6);
                                    if (data === '[DONE]') {
                                        // Stream finished
                                        $('#streamSpinner').hide();
                                        $('#streamStatus').text('Stream completed').removeClass('text-info');
                                        $('#streamStart').prop('disabled', false);
                                        $('#streamStop').prop('disabled', true);
                                        return;
                                    }
                                    
                                    try {
                                        const parsed = JSON.parse(data);
                                        if (parsed.token) {
                                            $('#streamOutput').append(document.createTextNode(parsed.token));
                                            tokenCount++;
                                            $('#streamTokenCount').text(tokenCount + ' tokens');
                                            // Scroll to bottom
                                            $('#streamOutput').scrollTop($('#streamOutput')[0].scrollHeight);
                                        }
                                        if (parsed.error) {
                                            $('#streamStatus').text('Error: ' + parsed.error).addClass('text-danger');
                                        }
                                    } catch (e) {
                                        // Not JSON, maybe raw text
                                        if (data.trim()) {
                                            $('#streamOutput').append(document.createTextNode(data));
                                        }
                                    }
                                }
                            });
                            
                            return readStream();
                        });
                    }
                    
                    return readStream();
                })
                .catch(error => {
                    if (error.name === 'AbortError') {
                        $('#streamStatus').text('Stream cancelled').addClass('text-warning');
                    } else {
                        $('#streamStatus').text('Stream error: ' + error.message).addClass('text-danger');
                    }
                    $('#streamSpinner').hide();
                    $('#streamStart').prop('disabled', false);
                    $('#streamStop').prop('disabled', true);
                });
            });

            // Stop Streaming
            $('#streamStop').on('click', function() {
                if (streamController) {
                    streamController.abort();
                    streamController = null;
                    $('#streamSpinner').hide();
                    $('#streamStart').prop('disabled', false);
                    $('#streamStop').prop('disabled', true);
                    $('#streamStatus').text('Stream stopped by user').addClass('text-warning');
                }
            });

            // Embeddings Form Submission
            $('#embedForm').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const formData = form.serialize();
                $('#embedSpinner').show();
                $('#embedStatus').text('Calculating embeddings...').removeClass('text-danger').addClass('text-info');
                
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Display embeddings (truncated)
                            $('#embedOutput1').text(JSON.stringify(response.embedding1.slice(0, 10), null, 2) + (response.embedding1.length > 10 ? ' ...' : ''));
                            $('#embedOutput2').text(JSON.stringify(response.embedding2.slice(0, 10), null, 2) + (response.embedding2.length > 10 ? ' ...' : ''));
                            $('#embedDim1').text('Dimension: ' + response.embedding1.length);
                            $('#embedDim2').text('Dimension: ' + response.embedding2.length);
                            
                            // Display similarity
                            const similarity = response.similarity;
                            $('#similarityScore').text(similarity.toFixed(4));
                            const percent = Math.min(100, Math.max(0, (similarity + 1) * 50)); // Convert -1..1 to 0..100
                            $('#similarityBar').css('width', percent + '%');
                            
                            $('#embedStatus').text('Completed').removeClass('text-info');
                        } else {
                            $('#embedStatus').text('Error: ' + response.error).addClass('text-danger');
                        }
                    },
                    error: function(xhr) {
                        $('#embedStatus').text('Request failed: ' + xhr.statusText).addClass('text-danger');
                    },
                    complete: function() {
                        $('#embedSpinner').hide();
                    }
                });
            });

            // Auto-scroll chat container
            $('#chatContainer').scrollTop($('#chatContainer')[0].scrollHeight);
        });
    </script>
</body>
</html>