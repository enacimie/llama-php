<?php

namespace Llama\Templates;

use Llama\Templates\BaseTemplate;

class CommandRTemplate extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $prompt = "";
        
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = trim($message['content']);
            
            $roleTag = match($role) {
                'system' => '<|SYSTEM_TOKEN|>',
                'user' => '<|USER_TOKEN|>',
                'assistant' => '<|CHATBOT_TOKEN|>',
                default => '<|USER_TOKEN|>', // Fallback
            };

            $prompt .= "{$roleTag}{$content}<|END_OF_TURN_TOKEN|>";
        }
        
        // Add generation prompt
        $prompt .= "<|CHATBOT_TOKEN|>";
        
        return $prompt;
    }
}
