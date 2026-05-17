<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Conversation\Enum;

/**
 * OpenAI/Ollama-compatible chat roles. The exact set of values matches
 * what the LLM API expects in the `messages[].role` field, so we ship
 * them verbatim.
 */
enum MessageRoleEnum: string
{
    /** Optional priming message at the head of the conversation. */
    case System = 'system';

    /** Human input. */
    case User = 'user';

    /** LLM-generated reply. */
    case Assistant = 'assistant';

    /** Result returned by a tool the LLM called — fed back to the model in the next turn. */
    case Tool = 'tool';
}
