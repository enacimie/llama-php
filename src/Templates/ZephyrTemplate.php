<?php

namespace Llama\Templates;

class ZephyrTemplate extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $this->validateMessages($messages);

        $parts = [];
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];
            $parts[] = "<|{$role}|>\n{$content}</s>";
        }

        // Add assistant prefix to prompt response
        $parts[] = "<|assistant|>\n";

        return implode("\n", $parts);
    }
}