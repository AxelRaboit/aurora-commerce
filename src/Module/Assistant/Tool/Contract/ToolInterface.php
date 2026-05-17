<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Tool\Contract;

use Aurora\Core\User\Entity\CoreUserInterface;

/**
 * A capability the LLM can call. The LLM sees `getName()`, `getDescription()`
 * and `getParameterSchema()` — same shape as OpenAI/Ollama function-calling.
 * When the model emits a tool call, the registry dispatches to `execute()`.
 *
 * Tools run with the conversation's owning user as the security boundary:
 * use it to scope DB queries / filesystem access. The conversation never
 * crosses user boundaries (a user A's assistant can't read user B's data).
 */
interface ToolInterface
{
    /** Stable identifier the LLM uses to call the tool (e.g. `aurora_search`). */
    public function getName(): string;

    /** One-line description the LLM reads to decide whether to call. */
    public function getDescription(): string;

    /**
     * JSON Schema for the `arguments` object the LLM passes back.
     *
     * @return array<string, mixed>
     */
    public function getParameterSchema(): array;

    /**
     * Run the tool with the model-supplied arguments. Return a human-
     * readable result string (or stringifiable structure) — that string
     * becomes the `tool` message content for the next LLM turn.
     *
     * Implementations must NEVER throw on user-recoverable errors; encode
     * the failure as a string the LLM can react to (e.g. "Path does not
     * exist: …").
     *
     * @param array<string, mixed> $arguments
     */
    public function execute(array $arguments, CoreUserInterface $user): string;
}
