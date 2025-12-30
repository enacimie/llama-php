<?php

namespace Llama;

use Llama\Templates\BaseTemplate;
use Llama\Templates\Llama3Template;
use Llama\Templates\Phi2Template;
use Llama\Templates\MistralTemplate;
use Llama\Templates\ZephyrTemplate;
use Llama\Templates\QwenTemplate;
use Llama\Templates\Qwen3Template;
use Llama\Templates\DeepSeekTemplate;
use Llama\Templates\GemmaTemplate;
use Llama\Templates\ChatMLTemplate;
use Llama\Templates\FalconTemplate;
use Llama\Templates\CommandRTemplate;
use Llama\Templates\VicunaTemplate;
use Llama\Exception\LlamaException;
use Llama\Transport\TransportInterface;

class Chat
{
    private Llama $llama;
    private BaseTemplate $template;

    public function __construct(
        string $binaryPath,
        string $modelPath,
        ?BaseTemplate $template = null,
        ?TransportInterface $transport = null
    ) {
        $this->llama = new Llama($binaryPath, $modelPath, $transport);
        $this->template = $template ?? self::detectTemplateFromModelPath($modelPath);
    }

    /**
     * Perform a chat conversation.
     *
     * @param array $messages Array of messages, each with 'role' and 'content'
     * @param array $options Generation options (same as Llama::generate)
     * @return string Assistant response
     */
    public function chat(array $messages, array $options = []): string
    {
        $prompt = $this->template->formatChat($messages);
        return $this->llama->generate($prompt, $options);
    }

    /**
     * Attempt to detect the appropriate chat template from the model filename.
     *
     * @param string $modelPath
     * @return BaseTemplate
     * @throws LlamaException If cannot detect a suitable template
     */
    public static function detectTemplateFromModelPath(string $modelPath): BaseTemplate
    {
        $filename = basename($modelPath);
        $lower = strtolower($filename);

        // Check for known model patterns
        if (str_contains($lower, 'gemma')) {
            return new GemmaTemplate();
        }
        if (str_contains($lower, 'command-r')) {
            return new CommandRTemplate();
        }
        if (str_contains($lower, 'falcon')) {
            return new FalconTemplate();
        }
        if (str_contains($lower, 'vicuna')) {
            return new VicunaTemplate();
        }
        if (str_contains($lower, 'hermes') || str_contains($lower, 'yi-') || str_contains($lower, 'chatml')) {
            return new ChatMLTemplate();
        }
        if (str_contains($lower, 'llama') || str_contains($lower, 'llama-3')) {
            return new Llama3Template();
        }
        if (str_contains($lower, 'phi-2') || str_contains($lower, 'phi2')) {
            return new Phi2Template();
        }
        if (str_contains($lower, 'mistral')) {
            return new MistralTemplate();
        }
        if (str_contains($lower, 'zephyr')) {
            return new ZephyrTemplate();
        }
        if (str_contains($lower, 'qwen')) {
            // Check for Qwen3 specifically
            if (str_contains($lower, 'qwen3')) {
                $template = new Qwen3Template();
                // Enable thinking mode for models with "thinking" or "thinker" in name
                if (str_contains($lower, 'thinking') || str_contains($lower, 'thinker')) {
                    $template->setThinkingMode(true);
                }
                return $template;
            }
            // For Qwen2/Qwen2.5, use the original template
            return new QwenTemplate();
        }
        if (str_contains($lower, 'deepseek')) {
            return new DeepSeekTemplate();
        }

        // Default to Llama3 template (common)
        return new Llama3Template();
    }
}