<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Llama\Llama;

// Paths to your llama.cpp binary and model
// Update these paths to match your system
$binaryPath = __DIR__ . '/../llama.cpp/build/bin/llama-cli';
$modelPath = __DIR__ . '/../models/qwen3-0.6b-q4_k_m.gguf';

// Check if files exist (optional)
if (!file_exists($binaryPath) || !file_exists($modelPath)) {
    echo "Please set the correct paths to llama-cli and model.\n";
    echo "Binary expected at: $binaryPath\n";
    echo "Model expected at: $modelPath\n";
    echo "\nTo download the Qwen3 model:\n";
    echo "wget -P models/ https://huggingface.co/enacimie/Qwen3-0.6B-Q4_K_M-GGUF/resolve/main/qwen3-0.6b-q4_k_m.gguf\n";
    exit(1);
}

$llama = new Llama($binaryPath, $modelPath);

echo "Generating text...\n";
$result = $llama->generate("Write a PHP function to calculate factorial.");
echo "Result:\n$result\n";

// With custom options
echo "\n--- With custom options (max_tokens=50, temperature=0.3) ---\n";
$result2 = $llama->generate("Explain recursion in programming.", [
    'max_tokens' => 50,
    'temperature' => 0.3,
]);
echo "Result:\n$result2\n";