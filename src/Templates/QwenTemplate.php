<?php

namespace Llama\Templates;

/**
 * Chat template for Qwen models (Qwen2, Qwen2.5, Qwen3 series).
 *
 * Uses the ChatML format with <|im_start|> and <|im_end|> tokens.
 * Compatible with:
 * - Qwen2 (7B, 14B, 72B)
 * - Qwen2.5 (0.5B, 1.5B, 3B, 7B, 14B, 32B, 72B)
 * - Qwen3 (all variants)
 * - Qwen-Chat instruct models
 */
class QwenTemplate extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $this->validateMessages($messages);

        $parts = [];
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];
            $parts[] = "<|im_start|>{$role}\n{$content}<|im_end|>";
        }

        // Add the assistant start token to prompt the model to respond
        $parts[] = "<|im_start|>assistant\n";

        return implode("\n", $parts);
    }
}