<?php

namespace Llama\Templates;

use Llama\Templates\BaseTemplate;

class ChatMLTemplate extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $prompt = "";
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = trim($message['content']);
            
            $prompt .= "<|im_start|>{$role}\n{$content}<|im_end|>\n";
        }
        
        // Add generation prompt
        $prompt .= "<|im_start|>assistant\n";
        
        return $prompt;
    }
}

