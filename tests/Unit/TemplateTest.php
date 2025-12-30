<?php

namespace Llama\Tests\Unit;

use Llama\Templates\Llama3Template;
use Llama\Templates\Phi2Template;
use Llama\Templates\MistralTemplate;
use Llama\Templates\ZephyrTemplate;
use Llama\Templates\QwenTemplate;
use Llama\Templates\Qwen3Template;
use Llama\Templates\DeepSeekTemplate;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    public function testLlama3Template()
    {
        $template = new Llama3Template();
        $messages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi'],
            ['role' => 'user', 'content' => 'How are you?'],
        ];
        $result = $template->formatChat($messages);
        $expected = "<|start_header_id|>user<|end_header_id|>\n\nHello<|eot_id|><|start_header_id|>assistant<|end_header_id|>\n\nHi<|eot_id|><|start_header_id|>user<|end_header_id|>\n\nHow are you?<|eot_id|><|start_header_id|>assistant<|end_header_id|>\n\n";
        $this->assertEquals($expected, $result);
    }

    public function testPhi2Template()
    {
        $template = new Phi2Template();
        $messages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi'],
        ];
        $result = $template->formatChat($messages);
        $expected = "Human: Hello\nAssistant: Hi\nAssistant:";
        $this->assertEquals($expected, $result);
    }

    public function testMistralTemplate()
    {
        $template = new MistralTemplate();
        $messages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi'],
            ['role' => 'user', 'content' => 'How are you?'],
        ];
        $result = $template->formatChat($messages);
        $expected = "<s>[INST] Hello [/INST] Hi</s><s>[INST] How are you? [/INST]";
        $this->assertEquals($expected, $result);
    }

    public function testZephyrTemplate()
    {
        $template = new ZephyrTemplate();
        $messages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi'],
        ];
        $result = $template->formatChat($messages);
        $expected = "<|user|>\nHello</s>\n<|assistant|>\nHi</s>\n<|assistant|>\n";
        $this->assertEquals($expected, $result);
    }

    public function testValidationThrowsOnInvalidRole()
    {
        $this->expectException(\InvalidArgumentException::class);
        $template = new Llama3Template();
        $template->formatChat([['role' => 'invalid', 'content' => 'test']]);
    }

    public function testValidationThrowsOnMissingKeys()
    {
        $this->expectException(\InvalidArgumentException::class);
        $template = new Llama3Template();
        $template->formatChat([['role' => 'user']]);
    }

    public function testQwenTemplate()
    {
        $template = new QwenTemplate();
        $messages = [
            ['role' => 'system', 'content' => 'You are helpful'],
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there'],
            ['role' => 'user', 'content' => 'How are you?'],
        ];
        $result = $template->formatChat($messages);
        $expected = "<|im_start|>system\nYou are helpful<|im_end|>\n<|im_start|>user\nHello<|im_end|>\n<|im_start|>assistant\nHi there<|im_end|>\n<|im_start|>user\nHow are you?<|im_end|>\n<|im_start|>assistant\n";
        $this->assertEquals($expected, $result);
    }

    public function testDeepSeekTemplate()
    {
        $template = new DeepSeekTemplate();
        $messages = [
            ['role' => 'system', 'content' => 'You are helpful'],
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there'],
            ['role' => 'user', 'content' => 'How are you?'],
        ];
        $result = $template->formatChat($messages);
        $expected = "<|im_start|>system\nYou are helpful<|im_end|>\n<|im_start|>user\nHello<|im_end|>\n<|im_start|>assistant\nHi there<|im_end|>\n<|im_start|>user\nHow are you?<|im_end|>\n<|im_start|>assistant\n";
        $this->assertEquals($expected, $result);
    }

    public function testQwen3TemplateNonThinking()
    {
        $template = new Qwen3Template();
        $template->setThinkingMode(false);
        $messages = [
            ['role' => 'system', 'content' => 'You are helpful'],
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there'],
            ['role' => 'user', 'content' => 'How are you?'],
        ];
        $result = $template->formatChat($messages);
        $expected = "<|im_start|>system\nYou are helpful<|im_end|>\n<|im_start|>user\nHello<|im_end|>\n<|im_start|>assistant\nHi there<|im_end|>\n<|im_start|>user\nHow are you?<|im_end|>\n<|im_start|>assistant\n";
        $this->assertEquals($expected, $result);
    }

    public function testQwen3TemplateThinking()
    {
        $template = new Qwen3Template();
        $template->setThinkingMode(true);
        $messages = [
            ['role' => 'system', 'content' => 'You are helpful'],
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there'],
            ['role' => 'user', 'content' => 'How are you?'],
        ];
        $result = $template->formatChat($messages);
        $expected = "<|im_start|>system\nYou are helpful<|im_end|>\n<|im_start|>user\nHello<|im_end|>\n<|im_start|>assistant\nHi there<|im_end|>\n<|im_start|>user\nHow are you?<|im_end|>\n<|im_start|>assistant\n<think>";
        $this->assertEquals($expected, $result);
    }

    public function testQwen3ParseThinkingOutput()
    {
        $template = new Qwen3Template();
        $template->setThinkingMode(true);

        // Test with thinking content
        $output = "Let me think about this...\nThis is interesting.</think>Here is my final answer.";
        $parsed = $template->parseThinkingOutput($output);
        $this->assertEquals("Let me think about this...\nThis is interesting.", $parsed['thinking']);
        $this->assertEquals("Here is my final answer.", $parsed['response']);

        // Test without </think> tag
        $output = "Just thinking without closing tag";
        $parsed = $template->parseThinkingOutput($output);
        $this->assertEquals("Just thinking without closing tag", $parsed['thinking']);
        $this->assertEquals("", $parsed['response']);

        // Test with <think> at start (should be stripped)
        $output = "<think>Thinking content</think>Response";
        $parsed = $template->parseThinkingOutput($output);
        $this->assertEquals("Thinking content", $parsed['thinking']);
        $this->assertEquals("Response", $parsed['response']);

        // Test with only closing tag
        $output = "</think>Only response";
        $parsed = $template->parseThinkingOutput($output);
        $this->assertEquals("", $parsed['thinking']);
        $this->assertEquals("Only response", $parsed['response']);
    }
}