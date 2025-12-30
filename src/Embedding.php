<?php

namespace Llama;

use Llama\Transport\TransportInterface;
use Llama\Transport\CliTransport;

class Embedding
{
    private TransportInterface $transport;

    public function __construct(string $binaryPath, string $modelPath, ?TransportInterface $transport = null)
    {
        // Ensure the transport supports embedding (i.e., has embed method)
        $this->transport = $transport ?? new CliTransport($binaryPath, $modelPath);
    }

    /**
     * Generate embedding vector for a given text.
     *
     * @param string $text
     * @param array $options Supported options: timeout, threads, batch_size, n_gpu_layers, ctx_size
     * @return array<float> Embedding vector
     */
    public function embed(string $text, array $options = []): array
    {
        return $this->transport->embed($text, $options);
    }
}