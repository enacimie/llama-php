<?php

namespace Llama\Templates;

/**
 * Chat template for Qwen3 models (non-thinking and thinking variants).
 *
 * Uses the ChatML format with <|im_start|> and <|im_end|> tokens.
 * For thinking models, automatically includes <think> tag for assistant responses.
 *
 * Compatible with:
 * - Qwen3 (4B, 8B, 14B, 32B, 72B, 235B-MOE)
 * - Qwen3-Instruct models
 * - Qwen3-Next variants
 * - Both thinking and non-thinking modes (auto-detected by filename)
 */
class Qwen3Template extends QwenTemplate
{
    private bool $thinkingMode = false;

    /**
     * Enable or disable thinking mode.
     *
     * @param bool $enabled
     * @return self
     */
    public function setThinkingMode(bool $enabled): self
    {
        $this->thinkingMode = $enabled;
        return $this;
    }

    /**
     * Check if thinking mode is enabled.
     *
     * @return bool
     */
    public function isThinkingMode(): bool
    {
        return $this->thinkingMode;
    }

    public function formatChat(array $messages): string
    {
        $this->validateMessages($messages);

        $parts = [];
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];
            $parts[] = "<|im_start|>{$role}\n{$content}<|im_end|>";
        }

        // For thinking models, add <think> tag to prompt the model to think
        if ($this->thinkingMode) {
            $parts[] = "<|im_start|>assistant\n<think>";
        } else {
            $parts[] = "<|im_start|>assistant\n";
        }

        return implode("\n", $parts);
    }

    /**
     * Parse model output to extract thinking content and final response.
     *
     * @param string $output Raw model output
     * @return array{thinking: string, response: string} Array with 'thinking' and 'response' keys
     */
    public function parseThinkingOutput(string $output): array
    {
        $thinking = '';
        $response = $output;
        
        // Remove known artifacts that might appear at the start
        $response = preg_replace('/^(\s*>\s*)+/', '', $response);

        // Look for </think> tag
        $thinkEndPos = strpos($response, '</think>');
        if ($thinkEndPos !== false) {
            $thinking = substr($response, 0, $thinkEndPos);
            $response = substr($response, $thinkEndPos + 8); // 8 = length of '</think>'
            
            // If thinking starts with <think>
            $thinkStartPos = strpos($thinking, '<think>');
            if ($thinkStartPos !== false) {
                $thinking = substr($thinking, $thinkStartPos + 7);
            }
        } elseif ($this->thinkingMode) {
             // If we forced thinking but didn't get a closing tag, check if we got an opening one
             $thinkStartPos = strpos($response, '<think>');
             if ($thinkStartPos !== false) {
                 // We have an opening tag but no closing tag, assume all subsequent text is thinking
                 // or model got cut off.
                 $thinking = substr($response, $thinkStartPos + 7);
                 $response = '';
             } else {
                 // Thinking mode enabled but no tags found at all.
                 // Assume everything is thinking (e.g. model stream cut off or forgot tags).
                 $thinking = $response;
                 $response = '';
             }
        }

        // If no thinking tags found, check for Qwen3-style thinking markers
        if (empty($thinking)) {
            $endThinkingPos = strpos($response, '[End thinking]');
            if ($endThinkingPos !== false) {
                $thinking = substr($response, 0, $endThinkingPos);
                $response = substr($response, $endThinkingPos + 14); // 14 = length of '[End thinking]'

                // Remove "[Start thinking]" prefix from thinking if present
                $startThinkingPos = strpos($thinking, '[Start thinking]');
                if ($startThinkingPos !== false) {
                    $thinking = substr($thinking, $startThinkingPos + 16);
                }
                $thinking = trim($thinking);
            }
        }

        // Final cleanup of response
        $response = trim($response);
        // Remove trailing > if it exists (common artifact if stop token missed)
        $response = preg_replace('/(\n>)+$/', '', $response);

        // Remove any remaining "[Start thinking]" prefix from response
        if (str_contains($response, '[Start thinking]')) {
            $response = preg_replace('/^\\[Start thinking\\]\\s*/', '', $response);
        }

        return [
            'thinking' => trim($thinking),
            'response' => $response,
        ];
    }
}