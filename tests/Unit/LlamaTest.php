<?php

namespace Llama\Tests\Unit;

use Llama\Llama;
use Llama\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

class LlamaTest extends TestCase
{
    public function testGenerateCallsTransportWithDefaults()
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('generate')
            ->with(
                'Hello world',
                [
                    'max_tokens' => 128,
                    'temperature' => 0.8,
                    'top_p' => 0.9,
                    'repeat_penalty' => 1.1,
                    'ctx_size' => 512,
                    'timeout' => 60,
                ]
            )
            ->willReturn('Generated text');

        $llama = new Llama('/fake/binary', '/fake/model.gguf', $transport);
        $result = $llama->generate('Hello world');

        $this->assertEquals('Generated text', $result);
    }

    public function testGenerateOverridesDefaults()
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('generate')
            ->with(
                'Test prompt',
                [
                    'max_tokens' => 50,
                    'temperature' => 0.5,
                    'top_p' => 0.9,
                    'repeat_penalty' => 1.1,
                    'ctx_size' => 512,
                    'timeout' => 60,
                    'threads' => 4,
                ]
            )
            ->willReturn('Custom generated');

        $llama = new Llama('/fake/binary', '/fake/model.gguf', $transport);
        $result = $llama->generate('Test prompt', [
            'max_tokens' => 50,
            'temperature' => 0.5,
            'threads' => 4,
        ]);

        $this->assertEquals('Custom generated', $result);
    }
}