# LlamaPHP Web Demo

This directory contains a modern web interface to demonstrate the capabilities of LlamaPHP, a robust PHP wrapper for llama.cpp.

## Features

1. **Interactive Chat**: Full conversation history with support for Qwen3 thinking mode and JSON schema output.
2. **Text Generation**: Simple prompt-based generation with configurable parameters (temperature, max tokens, top-p).
3. **Real-time Streaming**: Watch tokens appear as they're generated using the new `generateStream()` method.
4. **Embeddings**: Generate text embeddings and calculate cosine similarity (requires embedding-capable model).
5. **Configuration**: View model and binary information, test API endpoints.

## Requirements

- PHP 8.1 or higher
- Composer dependencies installed (`composer install`)
- Llama.cpp binary (`llama-cli`) compiled and accessible
- GGUF model file in the `models/` directory

## Installation

1. Ensure the main LlamaPHP project is set up:
   ```bash
   cd /path/to/llama-php
   composer install
   ```

2. Download a GGUF model and place it in the `models/` directory.

3. Compile llama.cpp or ensure `llama-cli` binary is in your PATH.

## Running the Demo

### Using PHP's Built-in Server

From the project root:
```bash
php -S localhost:8080 -t web/
```

Then open your browser to `http://localhost:8080/`.

### Using Apache/Nginx

Point your web server document root to the `web/` directory.

## API Endpoints

The demo uses AJAX calls to `api.php` with the following actions:

- `GET api.php?action=test` - Test connection and configuration
- `POST api.php?action=chat` - Send a chat message
- `POST api.php?action=generate` - Generate text from a prompt
- `POST api.php?action=stream` - Stream tokens in real-time (Server-Sent Events)
- `POST api.php?action=embed` - Generate embeddings and calculate similarity
- `POST api.php?action=reset` - Reset chat history

## New Features Showcased

This demo highlights the recent improvements to LlamaPHP:

- **Input Validation**: All options are validated with proper error messages
- **PSR-3 Logging**: Optional logging support (if logger is provided)
- **Token Streaming**: Real-time token delivery via generators
- **Process Cancellation**: Ability to stop generation mid-process
- **Specific Exceptions**: Granular error handling with custom exception classes

## Troubleshooting

### "No GGUF model found"
Ensure you have at least one `.gguf` file in the `models/` directory.

### "Binary not found or not executable"
Compile llama.cpp or ensure `llama-cli` is in your PATH. The demo checks multiple common locations.

### Streaming not working
Check that your transport supports the `generateStream()` method. The CliTransport included with LlamaPHP supports it.

### Embeddings failing
Not all models support embeddings. Look for models specifically trained for embeddings (e.g., "all-MiniLM", "bge", "e5").

## License

Same as the main LlamaPHP project.