<?php

namespace Llama\Templates;

class DeepSeekTemplate extends BaseTemplate
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