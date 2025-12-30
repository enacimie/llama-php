<?php

namespace Llama\Templates;

class Llama3Template extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $this->validateMessages($messages);

        $parts = [];
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];
            $parts[] = "<|start_header_id|>{$role}<|end_header_id|>\n\n{$content}<|eot_id|>";
        }

        // Add the assistant start token to prompt the model to respond
        $parts[] = "<|start_header_id|>assistant<|end_header_id|>\n\n";

        return implode('', $parts);
    }
}