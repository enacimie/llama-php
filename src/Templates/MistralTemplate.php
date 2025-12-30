<?php

namespace Llama\Templates;

class MistralTemplate extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        $this->validateMessages($messages);

        $parts = [];
        $i = 0;
        $count = count($messages);

        while ($i < $count) {
            $message = $messages[$i];
            $role = $message['role'];
            $content = $message['content'];

            if ($role === 'user') {
                $userContent = $content;
                $assistantContent = '';

                // Look ahead for assistant response
                if ($i + 1 < $count && $messages[$i + 1]['role'] === 'assistant') {
                    $assistantContent = $messages[$i + 1]['content'];
                    $i += 2;
                } else {
                    $i += 1;
                }

                if ($assistantContent !== '') {
                    $parts[] = "<s>[INST] {$userContent} [/INST] {$assistantContent}</s>";
                } else {
                    $parts[] = "<s>[INST] {$userContent} [/INST]";
                }
            } elseif ($role === 'system') {
                // System message typically prepended as a user instruction
                $parts[] = "<s>[INST] {$content} [/INST]";
                $i += 1;
            } else {
                // Assistant message without preceding user message (should not happen)
                $i += 1;
            }
        }

        // If the last message was from user, we already opened [INST] and need to close it
        // Our loop already adds the opening tag and expects a response.
        return implode('', $parts);
    }
}