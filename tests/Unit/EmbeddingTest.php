<?php

namespace Llama\Tests\Unit;

use Llama\Embedding;
use Llama\Transport\CliTransport;
use Llama\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

class EmbeddingTest extends TestCase
{
    public function testEmbedCallsTransport()
    {
        $transport = $this->getMockBuilder(CliTransport::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transport->expects($this->once())
            ->method('embed')
            ->with('Hello world', ['timeout' => 30])
            ->willReturn([0.1, 0.2, 0.3]);

        $embedding = new Embedding('/fake/binary', '/fake/model.gguf', $transport);
        $result = $embedding->embed('Hello world', ['timeout' => 30]);

        $this->assertEquals([0.1, 0.2, 0.3], $result);
    }

    public function testEmbedUsesDefaults()
    {
        $transport = $this->getMockBuilder(CliTransport::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transport->expects($this->once())
            ->method('embed')
            ->with('Test', [])
            ->willReturn([0.5]);

        $embedding = new Embedding('/fake/binary', '/fake/model.gguf', $transport);
        $result = $embedding->embed('Test');

        $this->assertEquals([0.5], $result);
    }


}