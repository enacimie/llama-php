<?php

namespace Llama\Templates;

use Llama\Templates\BaseTemplate;

class FalconTemplate extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $prompt = "";
        $systemMsg = null;

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemMsg = trim($message['content']);
                continue;
            }

            $role = match($message['role']) {
                'user' => 'User',
                'assistant' => 'Falcon', // Or 'Assistant' depending on specific fine-tune
                default => ucfirst($message['role']),
            };
            
            $content = trim($message['content']);
            $prompt .= "{$role}: {$content}\n";
        }

        // Prepend system message if exists
        if ($systemMsg) {
            $prompt = $systemMsg . "\n" . $prompt;
        }
        
        // Add generation prompt
        $prompt .= "Falcon:";
        
        return $prompt;
    }
}

