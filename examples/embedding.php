<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Llama\Embedding;

// Paths to your llama.cpp embedding binary and embedding model
// Note: For embeddings, you need the llama-embedding binary (not llama-cli)
$binaryPath = __DIR__ . '/../llama.cpp/build/bin/llama-embedding';
$modelPath = __DIR__ . '/../models/qwen3-embedding-0.6b-q4_k_m.gguf';

if (!file_exists($binaryPath) || !file_exists($modelPath)) {
    echo "Please set the correct paths to llama-embedding and embedding model.\n";
    echo "Binary expected at: $binaryPath\n";
    echo "Model expected at: $modelPath\n";
    echo "\nTo download the Qwen3 embedding model:\n";
    echo "wget -P models/ https://huggingface.co/enacimie/Qwen3-Embedding-0.6B-Q4_K_M-GGUF/resolve/main/qwen3-embedding-0.6b-q4_k_m.gguf\n";
    exit(1);
}

$embedding = new Embedding($binaryPath, $modelPath);

$text = "Machine learning with PHP is possible using llama.php";
echo "Generating embedding for: \"$text\"\n";

$vector = $embedding->embed($text);

echo "Embedding dimension: " . count($vector) . "\n";
echo "First 10 values:\n";
for ($i = 0; $i < min(10, count($vector)); $i++) {
    printf("%.6f ", $vector[$i]);
}
echo "\n...\n";

// Compare two texts
$text1 = "The cat sits on the mat";
$text2 = "A feline is resting on a rug";
$vector1 = $embedding->embed($text1);
$vector2 = $embedding->embed($text2);

// Simple cosine similarity (assuming vectors are normalized)
$dot = 0.0;
$norm1 = 0.0;
$norm2 = 0.0;
for ($i = 0; $i < count($vector1); $i++) {
    $dot += $vector1[$i] * $vector2[$i];
    $norm1 += $vector1[$i] * $vector1[$i];
    $norm2 += $vector2[$i] * $vector2[$i];
}
$similarity = $dot / (sqrt($norm1) * sqrt($norm2));
echo "\nSimilarity between \"$text1\" and \"$text2\": $similarity\n";