<?php

namespace Llama\Templates;

use Llama\Templates\BaseTemplate;

class VicunaTemplate extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $prompt = "";
        $systemMsg = "A chat between a curious user and an artificial intelligence assistant. The assistant gives helpful, detailed, and polite answers to the user's questions.";
        
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemMsg = trim($message['content']);
                continue;
            }
            
            $role = match($message['role']) {
                'user' => 'USER',
                'assistant' => 'ASSISTANT',
                default => strtoupper($message['role']),
            };
            
            $content = trim($message['content']);
            $prompt .= "{$role}: {$content}</s>";
        }
        
        // Prepend system message
        $finalPrompt = "{$systemMsg} {$prompt}ASSISTANT:";
        
        return $finalPrompt;
    }
}
