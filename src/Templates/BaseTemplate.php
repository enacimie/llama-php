<?php

namespace Llama\Templates;

abstract class BaseTemplate
{
    abstract public function formatChat(array $messages): string;

    protected function validateMessages(array $messages): void
    {
        foreach ($messages as $message) {
            if (!isset($message['role']) || !isset($message['content'])) {
                throw new \InvalidArgumentException(
                    'Each message must have "role" and "content" keys.'
                );
            }
            if (!in_array($message['role'], ['user', 'assistant', 'system'])) {
                throw new \InvalidArgumentException(
                    'Message role must be one of: user, assistant, system.'
                );
            }
        }
    }
}