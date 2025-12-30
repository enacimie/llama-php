<?php

namespace Llama\Templates;

use Llama\Templates\BaseTemplate;

class GemmaTemplate extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $prompt = "";
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = trim($message['content']);
            
            // Gemma uses <start_of_turn>role\ncontent<end_of_turn>
            // Roles are typically 'user' and 'model' (or 'assistant')
            $roleStr = match($role) {
                'system' => 'user', // Gemma often treats system prompt as first user message or just context
                'assistant' => 'model',
                default => $role,
            };

            $prompt .= "<start_of_turn>{$roleStr}\n{$content}<end_of_turn>\n";
        }
        
        // Add generation prompt
        $prompt .= "<start_of_turn>model\n";
        
        return $prompt;
    }
}
