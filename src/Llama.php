<?php

namespace Llama;

use Llama\Transport\CliTransport;
use Llama\Transport\TransportInterface;

class Llama
{
    private TransportInterface $transport;

    public function __construct(string $binaryPath, string $modelPath, ?TransportInterface $transport = null)
    {
        $this->transport = $transport ?? new CliTransport($binaryPath, $modelPath);
    }

    /**
     * Generate text based on a prompt.
     *
     * @param string $prompt
     * @param array $options Available options: max_tokens, temperature, top_p, repeat_penalty, ctx_size,
     *                      timeout, threads, seed, batch_size, n_gpu_layers, keep, top_k, grammar
     * @return string
     */
    public function generate(string $prompt, array $options = []): string
    {
        // Set default options
        $defaults = [
            'max_tokens' => 128,
            'temperature' => 0.8,
            'top_p' => 0.9,
            'repeat_penalty' => 1.1,
            'ctx_size' => 512,
            'timeout' => 60,
        ];

        $finalOptions = array_merge($defaults, $options);

        return $this->transport->generate($prompt, $finalOptions);
    }

    /**
     * Generate text with streaming output.
     *
     * @param string $prompt
     * @param array $options Same as generate()
     * @return \Generator Yields incremental chunks of text
     */
    public function generateStream(string $prompt, array $options = []): \Generator
    {
        // Set default options
        $defaults = [
            'max_tokens' => 128,
            'temperature' => 0.8,
            'top_p' => 0.9,
            'repeat_penalty' => 1.1,
            'ctx_size' => 512,
            'timeout' => 60,
        ];

        $finalOptions = array_merge($defaults, $options);

        // Check if transport supports streaming
        if (method_exists($this->transport, 'generateStream')) {
            yield from $this->transport->generateStream($prompt, $finalOptions);
        } else {
            // Fallback: yield the whole result at once
            $result = $this->transport->generate($prompt, $finalOptions);
            yield $result;
        }
    }
}
