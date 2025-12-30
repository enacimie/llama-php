# llama.php

**llama.php** is a robust, modular, and productive PHP wrapper for executing local Large Language Models (LLMs) using `llama.cpp` as the low-level inference engine. It provides a clean, secure API similar to OpenAI or Hugging Face, but 100% offline and self-contained.

## 🚀 Features

*   **PHP 8.1+**: Modern PHP with typed properties and safe coding practices.
*   **Local Inference**: Runs completely offline using your CPU (via `llama.cpp`).
*   **GGUF Support**: Compatible with the modern GGUF file format (quantized models like Q4_K_M, Q5_K_S).
*   **Secure**: Explicitly handles shell argument escaping to prevent injection.
*   **Flexible Transport**: Uses `proc_open` for robust CLI communication (FFI planned for future).
*   **Chat Templates**: Built-in templates for Llama 3, Phi‑2, Mistral, Zephyr, Qwen (2.x, 2.5, 3.x), and DeepSeek; auto‑detection from model filename.
*   **Thinking Mode**: Support for Qwen3 chain-of-thought reasoning with `<think>` tags and output parsing.
*   **Embedding Support**: Generate vector embeddings using `llama-embedding` binary with automatic parameter optimization for Qwen3 models (1024 dimension vectors).
*   **Reranking Support**: Compute query-document relevance scores with Qwen3 reranker models using `llama-embedding --pooling rank`.
*   **Model Downloader**: Built-in downloader for Hugging Face repositories with progress tracking.
*   **Comprehensive Options**: Control `temperature`, `top_p`, `ctx_size`, `threads`, `seed`, `batch_size`, `n_gpu_layers`, `grammar`, and more.
*   **Timeout Handling**: Configurable timeouts to prevent hanging processes.

## 📦 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/enacimie/llama-php
cd llama-php
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install llama.cpp

You need the `llama-cli` (or `main`) binary from `llama.cpp`. The library also requires the `llama-embedding` binary for embedding and reranking functionality.

**Option A: Compile from source (Recommended)**
```bash
git clone https://github.com/ggerganov/llama.cpp
cd llama.cpp
make
# The binaries are now at ./llama-cli and ./llama-embedding (or ./main in older versions)
```

**Option B: Download pre‑built binaries**
Check the [llama.cpp releases](https://github.com/ggerganov/llama.cpp/releases) for your platform. Make sure to download or build both `llama-cli` and `llama-embedding` binaries.

### 4. Download Models

Download GGUF models from Hugging Face. The following Qwen3 models have been specifically tested and optimized for llama.php:

| Model | Type | Link | Notes |
|-------|------|------|-------|
| Qwen3-0.6B-Q4_K_M | Text Generation | [enacimie/Qwen3-0.6B-Q4_K_M-GGUF](https://huggingface.co/enacimie/Qwen3-0.6B-Q4_K_M-GGUF) | General purpose Qwen3 model, supports thinking mode |
| Qwen3-Embedding-0.6B-Q4_K_M | Embeddings | [enacimie/Qwen3-Embedding-0.6B-Q4_K_M-GGUF](https://huggingface.co/enacimie/Qwen3-Embedding-0.6B-Q4_K_M-GGUF) | Embedding model with 1024 dimension vectors |
| Qwen3-Reranker-0.6B-Q4_K_M | Reranking | [enacimie/Qwen3-Reranker-0.6B-Q4_K_M-GGUF](https://huggingface.co/enacimie/Qwen3-Reranker-0.6B-Q4_K_M-GGUF) | Reranking model for query-document relevance scoring |

**Quick download example:**
```bash
# Create models directory
mkdir -p models

# Download tested Qwen3 models
wget -P models/ https://huggingface.co/enacimie/Qwen3-0.6B-Q4_K_M-GGUF/resolve/main/qwen3-0.6b-q4_k_m.gguf
wget -P models/ https://huggingface.co/enacimie/Qwen3-Embedding-0.6B-Q4_K_M-GGUF/resolve/main/qwen3-embedding-0.6b-q4_k_m.gguf
wget -P models/ https://huggingface.co/enacimie/Qwen3-Reranker-0.6B-Q4_K_M-GGUF/resolve/main/qwen3-reranker-0.6b-q4_k_m.gguf
```

Place downloaded models in the `models/` directory within the project.

## ⚡ Quick Start

### Basic Generation

```php
use Llama\Llama;

require 'vendor/autoload.php';

// Path to your llama-cli binary and the model
$binaryPath = __DIR__ . '/llama.cpp/build/bin/llama-cli';
$modelPath = __DIR__ . '/models/qwen3-0.6b-q4_k_m.gguf';

$llm = new Llama(binaryPath: $binaryPath, modelPath: $modelPath);

try {
    $result = $llm->generate("Write a hello world in PHP.");
    echo $result;
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Chat (Conversational AI)

```php
use Llama\Chat;

$chat = new Chat($binaryPath, $modelPath);
echo $chat->chat([
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'What is PHP?'],
    ['role' => 'assistant', 'content' => 'PHP is a server-side scripting language...'],
    ['role' => 'user', 'content' => 'Is it still used in 2025?']
]);

// Custom template (optional)
use Llama\Templates\MistralTemplate;
$chat = new Chat($binaryPath, $modelPath, new MistralTemplate());
```

### Embedding Generation

```php
use Llama\Embedding;

$emb = new Embedding($binaryPath, '/path/to/embedding-model.gguf');
$vector = $emb->embed("Machine learning in PHP");
print_r($vector); // [0.12, -0.45, ...]
```

**Note for Qwen3 Embedding models**: When using Qwen3 embedding models (e.g., `Qwen3-Embedding-0.6B-Q4_K_M`), the library automatically sets appropriate parameters: `--pooling last` and `--embd-normalize 2` for optimal vector generation. The embedding dimension is 1024 for this model.

### Reranking

```php
use Llama\Reranker;

$reranker = new Reranker($binaryPath, '/path/to/reranker-model.gguf');
$score = $reranker->rerank("What is PHP?", "PHP is a popular scripting language for web development.");
echo "Relevance score: $score\n"; // Higher scores indicate better relevance
```

**Note for Qwen3 Reranker models**: When using Qwen3 reranker models (e.g., `Qwen3-Reranker-0.6B-Q4_K_M`), the library automatically sets `--pooling rank` for optimal relevance scoring. The model expects query and document separated by a tab character (`\t`) by default, which can be customized with the `cls_separator` option.

## 🛠️ Advanced Usage

### Available Options

All options are passed as an associative array to `generate()`, `chat()`, or `embed()`.

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `max_tokens` | int | 128 | Maximum number of tokens to generate. |
| `temperature` | float | 0.8 | Sampling temperature (higher = more creative). |
| `top_p` | float | 0.9 | Top‑p sampling (nucleus sampling). |
| `repeat_penalty` | float | 1.1 | Penalty for repeated tokens. |
| `ctx_size` | int | 512 | Context window size. |
| `timeout` | int | 60 | Process timeout in seconds. |
| `threads` | int | – | Number of CPU threads to use. |
| `seed` | int | – | Random seed for reproducibility. |
| `batch_size` | int | – | Batch size for prompt processing. |
| `n_gpu_layers` | int | – | Number of layers to offload to GPU (if supported). |
| `top_k` | int | – | Top‑k sampling (keep only top k tokens). |
| `keep` | int | – | Number of tokens to keep from the initial prompt. |
| `grammar` | string | – | Path to a GBNF grammar file. |

Example with custom options:

```php
$result = $llm->generate("Write a story about a robot.", [
    'max_tokens' => 256,
    'temperature' => 0.7,
    'threads' => 8,
    'seed' => 42,
]);
```

### Custom Chat Templates

The library includes templates for Llama 3, Phi‑2, Mistral, Zephyr, Qwen (2.x, 2.5, 3.x), and DeepSeek. You can also create your own by extending `Llama\Templates\BaseTemplate`.

```php
use Llama\Templates\BaseTemplate;

class MyTemplate extends BaseTemplate
{
    public function formatChat(array $messages): string
    {
        // Your formatting logic
    }
}
```

Pass your template to the `Chat` constructor:

```php
$chat = new Chat($binaryPath, $modelPath, new MyTemplate());
```

### Qwen3 Thinking Mode

For Qwen3 models that support chain-of-thought reasoning (e.g., models with "Thinking" in the name), the library includes special handling:

```php
use Llama\Templates\Qwen3Template;

// Create template with thinking mode enabled
$template = new Qwen3Template();
$template->setThinkingMode(true);

$chat = new Chat($binaryPath, $modelPath, $template);
$response = $chat->chat($messages);

// Parse thinking content from raw output
$parsed = $template->parseThinkingOutput($response);
echo "Thinking: " . $parsed['thinking'] . "\n";
echo "Response: " . $parsed['response'] . "\n";
```

The template automatically adds `<think>` to the prompt for thinking models, and you can parse the output to separate thinking content from the final response.

### Structured Output (JSON Schema)

Force the model to output valid JSON conforming to a specific schema. This is powered by `llama.cpp`'s structured output capability.

```php
use Llama\Schema\JsonSchemaBuilder;

// Define the schema
$schema = JsonSchemaBuilder::build(
    JsonSchemaBuilder::object([
        'name' => 'string',
        'age' => 'integer',
        'skills' => JsonSchemaBuilder::list('string')
    ])
);

// Generate
$json = $llm->generate("Generate a user profile for Alice, 25, expert in PHP.", [
    'json_schema' => $schema,
    'temperature' => 0.1
]);

print_r(json_decode($json, true));
/*
Array
(
    [name] => Alice
    [age] => 25
    [skills] => Array
        (
            [0] => PHP
        )
)
*/
```

### Direct Transport Access

If you need low‑level control, you can use the `CliTransport` directly:

```php
use Llama\Transport\CliTransport;

$transport = new CliTransport($binaryPath, $modelPath);
$text = $transport->generate('Hello world', ['max_tokens' => 50]);
$vector = $transport->embed('Text to embed', ['threads' => 4]);
```

## 💻 Command Line Interface

The library includes a CLI tool `bin/llama` for interactive use:

```bash
# Generate text
./bin/llama generate --model /path/to/model.gguf --prompt "Hello world"

# Single-turn chat
./bin/llama chat --model /path/to/model.gguf --system "You are helpful" --prompt "What is PHP?"

# Generate embeddings
./bin/llama embed --model /path/to/embedding.gguf --text "Machine learning"

# Interactive multi-turn chat
./bin/llama interactive --model /path/to/model.gguf

# Qwen3 with thinking mode
./bin/llama chat --model qwen3-7b-thinking.gguf --template qwen3 --thinking --prompt "Explain quantum computing"
```

### CLI Options

| Option | Description |
|--------|-------------|
| `--binary` | Path to llama.cpp binary (auto-detected) |
| `--model` | Path to GGUF model file (required) |
| `--template` | Chat template (llama3, phi2, mistral, zephyr, qwen, qwen3, deepseek) |
| `--thinking` | Enable thinking mode for Qwen3 models (chain-of-thought) |
| `--max_tokens` | Maximum tokens to generate (default: 128) |
| `--temperature` | Sampling temperature (default: 0.8) |
| `--top_p` | Top‑p sampling (default: 0.9) |
| `--repeat_penalty` | Repeat penalty (default: 1.1) |
| `--ctx_size` | Context window size (default: 512) |
| `--timeout` | Process timeout in seconds (default: 60) |
| `--threads` | CPU threads to use |
| `--seed` | Random seed |

### Installation Helper

You can also use the CLI to help install llama.cpp:

```bash
./bin/llama install
```

This will check for an existing llama.cpp binary and offer to clone and compile it automatically.

The CLI also supports interactive mode with commands:
- `/exit` – quit
- `/clear` – clear conversation history
- `/system <message>` – change system message

## 📥 Downloading Models

The CLI includes a powerful model downloader for Hugging Face repositories:

```bash
# List available GGUF files
./bin/llama download --repo TheBloke/Llama-2-7B-GGUF --list

# Download a specific GGUF file
./bin/llama download --repo TheBloke/Llama-2-7B-GGUF --file llama-2-7b.Q4_K_M.gguf

# Download to custom directory
./bin/llama download --repo TheBloke/Llama-2-7B-GGUF --file llama-2-7b.Q4_K_M.gguf --output ./models

# Use with private/gated models (requires token)
./bin/llama download --repo username/private-model --token hf_xxxxxxxxxxxxx --list
```

Features:
- **Smart filtering**: Automatically lists only GGUF files
- **Progress indicators**: Shows download progress with file size
- **Resume support**: Partial downloads are detected and resumed (future)
- **Authentication**: Support for Hugging Face tokens for private models
- **ModelScope**: Planned future support for ModelScope repositories

## 🏗️ Architecture

```
src/
├── Llama.php                 # Main class for text generation
├── Chat.php                  # Conversational AI with templating
├── Embedding.php             # Embedding vector generation
├── Templates/                # Chat template formatters
│   ├── BaseTemplate.php
│   ├── Llama3Template.php
│   ├── Phi2Template.php
│   ├── MistralTemplate.php
│   ├── ZephyrTemplate.php
│   ├── QwenTemplate.php
│   ├── Qwen3Template.php
│   └── DeepSeekTemplate.php
├── Exception/
│   └── LlamaException.php
└── Transport/
    ├── CliTransport.php      # CLI communication (proc_open)
    └── TransportInterface.php
```

## 👤 Autor

**Eduardo Nacimiento-García**
📧 [enacimie@ull.edu.es](mailto:enacimie@ull.edu.es)

## 🤝 Contributing

Contributions are welcome! Please submit a PR.

## 📄 License

MIT
