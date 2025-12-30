<?php

namespace Llama\Transport;

interface TransportInterface
{
    public function generate(string $prompt, array $options = []): string;

    /**
     * Generate embedding vector for a given text.
     *
     * @param string $text
     * @param array $options
     * @return array<float>
     */
    public function embed(string $text, array $options = []): array;

    /**
     * Compute reranking score(s) for a query-document pair.
     *
     * @param string $query The search query
     * @param string $document The document text to rank
     * @param array $options Supported options: timeout, threads, batch_size, n_gpu_layers, ctx_size, cls_separator
     * @return float|array<float> Reranking score(s)
     */
    public function rerank(string $query, string $document, array $options = []): float|array;

    /**
     * Compute reranking scores for multiple query-document pairs.
     *
     * @param string $query The search query
     * @param array<string> $documents Array of document texts to rank
     * @param array $options Supported options: timeout, threads, batch_size, n_gpu_layers, ctx_size, cls_separator
     * @return array<float> Array of scores in same order as documents
     */
    public function rerankMultiple(string $query, array $documents, array $options = []): array;
}
