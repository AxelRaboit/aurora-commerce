<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Llm\Contract;

interface ChatClientInterface
{
    /**
     * Send a chat completion request to the LLM. The response shape mirrors
     * the Ollama / OpenAI `chat` API:
     *   [
     *     'role'       => 'assistant',
     *     'content'    => string,
     *     'tool_calls' => list<{id, type: 'function', function: {name, arguments: string|array}}>|null,
     *   ].
     *
     * @param list<array<string, mixed>> $messages chat history in OpenAI format (role/content/tool_call_id/…)
     * @param list<array<string, mixed>> $tools    OpenAI-style tool schemas, empty list disables tool calling
     *
     * @return array{role: string, content: string, tool_calls: list<array<string, mixed>>|null}
     */
    public function chat(array $messages, array $tools = []): array;

    /** Name of the currently configured chat model (e.g. `qwen3:8b`). */
    public function getModel(): string;
}
