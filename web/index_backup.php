<?php

use Llama\Chat;
use Llama\Templates\Qwen3Template;
use Llama\Schema\JsonSchemaBuilder;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

// Configuration
$modelDir = __DIR__ . '/../models';
// Find the first GGUF file in the models directory
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

if (!$modelPath) {
    $error = "No GGUF model found in <code>models/</code> directory.";
}

// Handle Reset
if (isset($_POST['reset'])) {
    $_SESSION['messages'] = [];
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Initialize Chat History
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [
        ['role' => 'system', 'content' => 'You are a helpful AI assistant powered by LlamaPHP.']
    ];
}

// Handle Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $modelPath) {
    $userMsg = trim($_POST['message']);
    
    if (!empty($userMsg)) {
        $_SESSION['messages'][] = ['role' => 'user', 'content' => $userMsg];
        
        try {
            // Check if it's a Qwen3 model to enable thinking
            $template = null;
            if (str_contains(strtolower(basename($modelPath)), 'qwen3')) {
                $template = new Qwen3Template();
                // Check if user wants thinking mode (checkbox)
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

            // JSON Schema Demo
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
                $options['temperature'] = 0.1; // Lower temp for structured output
                
                // Append instruction to user message if not already there
                $userMsg .= " (Respond in JSON format describing a movie)";
                $_SESSION['messages'][count($_SESSION['messages']) - 1]['content'] = $userMsg;
            }
            
            // Generate response
            $response = $chat->chat($_SESSION['messages'], $options);

            // If thinking mode was used, we might want to parse it
            if ($template instanceof Qwen3Template && isset($_POST['thinking'])) {
                $parsed = $template->parseThinkingOutput($response);
                $finalResponse = $parsed['response'];
                $thinkingContent = $parsed['thinking'];
                
                // Store thinking separately if you want to display it differently
                // For simplicity, we'll append it formatted for display in history, 
                // but usually you'd store structured data. 
                // Here we keep it simple for the demo.
                $_SESSION['messages'][] = ['role' => 'assistant', 'content' => $response];
            } else {
                $_SESSION['messages'][] = ['role' => 'assistant', 'content' => $response];
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LlamaPHP Web Chat</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f4f4f9; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .chat-box { height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 4px; background: #fafafa; }
        .message { margin-bottom: 15px; padding: 10px; border-radius: 8px; max-width: 80%; }
        .message.user { background: #007bff; color: white; margin-left: auto; }
        .message.assistant { background: #e9ecef; color: #333; margin-right: auto; }
        .message.system { background: #ffeeba; color: #856404; width: 100%; max-width: 100%; text-align: center; font-size: 0.9em; }
        .input-group { display: flex; gap: 10px; }
        textarea { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: none; height: 50px; }
        button { padding: 0 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        button.reset { background: #6c757d; }
        button.reset:hover { background: #5a6268; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px; margin-bottom: 20px; }
        .meta { font-size: 0.8em; color: #666; margin-bottom: 10px; }
        pre { white-space: pre-wrap; background: #f8f9fa; padding: 10px; border-radius: 4px; }
        details { margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; background: #fff; }
        summary { cursor: pointer; font-weight: bold; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🦙 LlamaPHP Chat</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="meta">
            <strong>Model:</strong> <?= $modelPath ? basename($modelPath) : 'None' ?><br>
            <strong>Binary:</strong> <?= htmlspecialchars($binaryPath) ?>
        </div>

        <div class="chat-box" id="chatBox">
            <?php foreach ($_SESSION['messages'] as $msg): ?>
                <div class="message <?= $msg['role'] ?>">
                    <strong><?= ucfirst($msg['role']) ?>:</strong>
                    <?php 
                        // Simple formatting for thinking tags if present in history
                        $content = htmlspecialchars($msg['content']);
                        $content = str_replace(["&lt;think&gt;", "&lt;/think&gt;"], ["<details><summary>Thinking...</summary><pre>", "</pre></details>"], $content);
                        echo nl2br($content);
                    ?>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" class="input-group">
            <textarea name="message" placeholder="Type a message..." required autofocus></textarea>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <?php if ($modelPath && str_contains(strtolower(basename($modelPath)), 'qwen3')): ?>
                    <label style="display: flex; align-items: center; font-size: 0.8em; white-space: nowrap;">
                        <input type="checkbox" name="thinking" value="1" checked> Thinking
                    </label>
                <?php endif; ?>
                <label style="display: flex; align-items: center; font-size: 0.8em; white-space: nowrap;">
                    <input type="checkbox" name="json_mode" value="1"> JSON Mode (Movie)
                </label>
            </div>
            <button type="submit">Send</button>
            <button type="submit" name="reset" value="1" class="reset" formnovalidate>Reset</button>
        </form>
    </div>
    <script>
        // Auto-scroll to bottom
        var chatBox = document.getElementById("chatBox");
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>
</body>
</html>