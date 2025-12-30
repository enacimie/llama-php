<?php

namespace Llama;

use Llama\Transport\TransportInterface;
use Llama\Transport\CliTransport;

class Reranker
{
    private TransportInterface $transport;

    public function __construct(string $binaryPath, string $modelPath, ?TransportInterface $transport = null)
    {
        // Ensure the transport supports reranking (i.e., has rerank method)
        $this->transport = $transport ?? new CliTransport($binaryPath, $modelPath);
    }

    /**
     * Compute reranking score(s) for a query-document pair.
     *
     * For Qwen3 reranker models, this returns a single score (float) indicating
     * the relevance of the document to the query. Higher scores indicate better relevance.
     *
     * @param string $query The search query
     * @param string $document The document text to rank
     * @param array $options Supported options: timeout, threads, batch_size, n_gpu_layers, ctx_size, cls_separator
     * @return float|array<float> Reranking score(s). For Qwen3 models, returns a single float score.
     */
    public function rerank(string $query, string $document, array $options = []): float|array
    {
        return $this->transport->rerank($query, $document, $options);
    }

    /**
     * Compute reranking scores for multiple query-document pairs.
     *
     * @param string $query The search query
     * @param array<string> $documents Array of document texts to rank
     * @param array $options Supported options: timeout, threads, batch_size, n_gpu_layers, ctx_size, cls_separator
     * @return array<float> Array of scores in same order as documents
     */
    public function rerankMultiple(string $query, array $documents, array $options = []): array
    {
        return $this->transport->rerankMultiple($query, $documents, $options);
    }
}