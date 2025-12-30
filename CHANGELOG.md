# Changelog

## [Unreleased] - 2025-12-27

### Fixed
- **Infinite Loop in Generation**: Fixed a critical issue where `CliTransport` would hang indefinitely. Added `--single-turn` flag to `llama-cli` commands to ensure the process exits after generating the response.
- **Output Artifacts**: Improved output parsing in `CliTransport`. The library now robustly strips:
    - The "Loading model..." banner.
    - The prompt echo (even when `llama-cli` forces it).
    - Performance statistics logs (e.g., `[ Prompt: ... | Generation: ... ]`).
    - "Exiting..." messages.
- **Qwen3 Thinking Parsing**: Updated `Qwen3Template` to handle cases where the model generates "thinking" content without explicit `<think>` tags, preventing data loss in chain-of-thought responses.
- **Unit Tests**:
    - Fixed `TemplateTest` to match robust parsing logic.
    - Removed invalid `EmbeddingTest` case (type safety validation).

### Added
- **Reranking Support**: Added `Reranker` class for query-document relevance scoring with support for Qwen3 reranker models. Includes API endpoint and CLI integration.

### Changed
- **CLI Interaction**: Forced `--color off` and `--no-display-prompt` in `CliTransport` to ensure cleaner output capture.
- **Process Management**: Improved `proc_open` stream handling and resource cleanup in `CliTransport`.
- **Embedding Support**: Enhanced Qwen3 embedding model compatibility with automatic parameter detection (`--pooling last`, `--embd-normalize 2`). Improved error handling for embedding processes that exit with signal -1.

## [0.1.0] - Initial Release
- Basic text generation (`Llama` class).
- Chat interface (`Chat` class).
- Embeddings support (`Embedding` class).
- Support for multiple templates (Llama3, Mistral, Qwen, Phi, etc.).
- CLI tool (`bin/llama`).
