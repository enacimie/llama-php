<?php

namespace Llama\Tests\Unit;

use Llama\Chat;
use Llama\Templates\BaseTemplate;
use Llama\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;

class ChatTest extends TestCase
{
    public function testChatFormatsPromptAndCallsLlama()
    {
        $template = $this->createMock(BaseTemplate::class);
        $template->expects($this->once())
            ->method('formatChat')
            ->with([
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'assistant', 'content' => 'Hi there'],
                ['role' => 'user', 'content' => 'How are you?'],
            ])
            ->willReturn('Formatted prompt');

        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('generate')
            ->with('Formatted prompt', ['max_tokens' => 128, 'temperature' => 0.8, 'top_p' => 0.9, 'repeat_penalty' => 1.1, 'ctx_size' => 512, 'timeout' => 60])
            ->willReturn('Assistant response');

        $chat = new Chat('/fake/binary', '/fake/model.gguf', $template, $transport);
        $result = $chat->chat([
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there'],
            ['role' => 'user', 'content' => 'How are you?'],
        ]);

        $this->assertEquals('Assistant response', $result);
    }

    public function testChatWithCustomOptions()
    {
        $template = $this->createMock(BaseTemplate::class);
        $template->method('formatChat')->willReturn('Formatted');

        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('generate')
            ->with('Formatted', ['max_tokens' => 50, 'temperature' => 0.2, 'top_p' => 0.9, 'repeat_penalty' => 1.1, 'ctx_size' => 512, 'timeout' => 60, 'threads' => 8])
            ->willReturn('Response');

        $chat = new Chat('/fake/binary', '/fake/model.gguf', $template, $transport);
        $result = $chat->chat([['role' => 'user', 'content' => 'Hi']], [
            'max_tokens' => 50,
            'temperature' => 0.2,
            'threads' => 8,
        ]);

        $this->assertEquals('Response', $result);
    }

    public function testDetectTemplateFromModelPath()
    {
        // This test uses the actual detection logic
        $this->assertInstanceOf(
            \Llama\Templates\Llama3Template::class,
            Chat::detectTemplateFromModelPath('/path/to/llama-3-8b-instruct.gguf')
        );
        $this->assertInstanceOf(
            \Llama\Templates\Phi2Template::class,
            Chat::detectTemplateFromModelPath('/path/to/phi-2.Q4_K_M.gguf')
        );
        $this->assertInstanceOf(
            \Llama\Templates\MistralTemplate::class,
            Chat::detectTemplateFromModelPath('/path/to/mistral-7b-instruct.gguf')
        );
        $this->assertInstanceOf(
            \Llama\Templates\ZephyrTemplate::class,
            Chat::detectTemplateFromModelPath('/path/to/zephyr-7b-beta.gguf')
        );
        // Default
        $this->assertInstanceOf(
            \Llama\Templates\Llama3Template::class,
            Chat::detectTemplateFromModelPath('/path/to/unknown.gguf')
        );
    }
}