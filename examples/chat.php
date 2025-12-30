<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Llama\Chat;

// Paths to your llama.cpp binary and model
$binaryPath = __DIR__ . '/../llama.cpp/build/bin/llama-cli';
$modelPath = __DIR__ . '/../models/qwen3-0.6b-q4_k_m.gguf';

if (!file_exists($binaryPath) || !file_exists($modelPath)) {
    echo "Please set the correct paths to llama-cli and model.\n";
    echo "Binary expected at: $binaryPath\n";
    echo "Model expected at: $modelPath\n";
    echo "\nTo download the Qwen3 model:\n";
    echo "wget -P models/ https://huggingface.co/enacimie/Qwen3-0.6B-Q4_K_M-GGUF/resolve/main/qwen3-0.6b-q4_k_m.gguf\n";
    exit(1);
}

$chat = new Chat($binaryPath, $modelPath);

$messages = [
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'What is the capital of France?'],
    ['role' => 'assistant', 'content' => 'The capital of France is Paris.'],
    ['role' => 'user', 'content' => 'What is its population?'],
];

echo "Chat conversation:\n";
foreach ($messages as $msg) {
    echo "{$msg['role']}: {$msg['content']}\n";
}

echo "\n--- Assistant response ---\n";
$response = $chat->chat($messages);
echo "$response\n";

// You can also pass custom generation options
$response2 = $chat->chat([
    ['role' => 'user', 'content' => 'Write a short poem about PHP.']
], [
    'max_tokens' => 100,
    'temperature' => 0.9,
]);
echo "\n--- Poem ---\n";
echo $response2 . "\n";