<?php

/**
 * Reranking Example for llama.php
 * 
 * This example shows how to use the Reranker class to compute relevance scores
 * between queries and documents using Qwen3 reranker models.
 * 
 * Requirements:
 * - llama-embedding binary from llama.cpp (compiled with embedding support)
 * - Qwen3-Reranker GGUF model (e.g., qwen3-reranker-0.6b-q4_k_m.gguf)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Llama\Reranker;

// Paths to your llama-embedding binary and reranker model
// Update these paths to match your system
$binaryPath = __DIR__ . '/../llama.cpp/build/bin/llama-embedding';
$modelPath = __DIR__ . '/../models/qwen3-reranker-0.6b-q4_k_m.gguf';

// Check if files exist
if (!file_exists($binaryPath)) {
    echo "Error: llama-embedding binary not found at: $binaryPath\n";
    echo "Please compile llama.cpp with 'make' and ensure llama-embedding exists.\n";
    exit(1);
}

if (!file_exists($modelPath)) {
    echo "Error: Reranker model not found at: $modelPath\n";
    echo "Please download a Qwen3 reranker model from Hugging Face:\n";
    echo "https://huggingface.co/enacimie/Qwen3-Reranker-0.6B-Q4_K_M-GGUF\n";
    exit(1);
}

echo "=== Reranking Example ===\n\n";

// Create the reranker instance
$reranker = new Reranker($binaryPath, $modelPath);

// Define a query and documents to rank
$query = "What is PHP?";
$documents = [
    "PHP is a popular scripting language for web development.",
    "Python is a high-level programming language known for its readability.",
    "JavaScript is a programming language used for web browsers.",
    "PHP supports object-oriented programming and has a large ecosystem of frameworks."
];

echo "Query: \"$query\"\n\n";
echo "Documents to rank:\n";
foreach ($documents as $i => $doc) {
    echo "  " . ($i + 1) . ". " . $doc . "\n";
}

echo "\n--- Computing relevance scores ---\n";

try {
    // Method 1: Score a single query-document pair
    echo "\n1. Single document scoring:\n";
    $score = $reranker->rerank($query, $documents[0]);
    echo "   Document 1 score: " . formatScore($score) . "\n";
    
    // Method 2: Score multiple documents at once (more efficient)
    echo "\n2. Multiple documents scoring:\n";
    $scores = $reranker->rerankMultiple($query, $documents);
    
    foreach ($scores as $i => $score) {
        echo "   Document " . ($i + 1) . " score: " . formatScore($score) . "\n";
    }
    
    // Method 3: With custom options
    echo "\n3. Custom separator example:\n";
    $scoreCustom = $reranker->rerank($query, $documents[0], [
        'cls_separator' => "\t",  // Default is tab, but can be changed
        'threads' => 4,           // Use 4 CPU threads
        'timeout' => 30,          // 30 second timeout
    ]);
    echo "   With custom options: " . formatScore($scoreCustom) . "\n";
    
    // Sort documents by relevance
    echo "\n--- Ranked results (most to least relevant) ---\n";
    $rankedDocs = array_combine($documents, $scores);
    arsort($rankedDocs);
    
    $rank = 1;
    foreach ($rankedDocs as $doc => $score) {
        $shortDoc = (strlen($doc) > 60) ? substr($doc, 0, 57) . '...' : $doc;
        echo "  $rank. Score: " . formatScore($score) . " - \"$shortDoc\"\n";
        $rank++;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure:\n";
    echo "1. llama-embedding binary is executable\n";
    echo "2. The model file is a valid GGUF reranker model\n";
    echo "3. You have sufficient memory to load the model\n";
    exit(1);
}

/**
 * Format a score for display
 * 
 * @param mixed $score Score value (float or array)
 * @return string Formatted score
 */
function formatScore($score): string {
    if (is_array($score)) {
        // If the model returns an array (e.g., embedding vector), show first value
        return number_format($score[0] ?? 0.0, 6);
    }
    return number_format($score, 6);
}

echo "\n=== Example complete ===\n";
echo "Note: Higher scores indicate better relevance to the query.\n";