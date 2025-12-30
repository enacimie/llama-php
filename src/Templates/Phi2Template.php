<?php

namespace Llama\Templates;

class Phi2Template extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $this->validateMessages($messages);

        $parts = [];
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];
            // Phi-2 expects "Human:" and "Assistant:" prefixes
            if ($role === 'user') {
                $parts[] = "Human: {$content}\n";
            } elseif ($role === 'assistant') {
                $parts[] = "Assistant: {$content}\n";
            } elseif ($role === 'system') {
                // System message often placed at beginning
                $parts[] = "System: {$content}\n";
            }
        }

        // Prompt for the assistant's response
        $parts[] = "Assistant:";

        return implode('', $parts);
    }
}