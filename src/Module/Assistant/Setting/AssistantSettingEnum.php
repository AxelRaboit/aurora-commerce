<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Setting;

use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Module-level settings surfaced on the `/backend/settings` page under
 * the "assistant" tab. The Ollama URL itself stays in the env (less
 * likely to need a runtime change, and keeping a remote URL out of the
 * DB avoids accidental leakage on a dump) — everything tunable at
 * runtime lives here.
 *
 * Defaults match what {@see OllamaChatClient} was constructor-wired with
 * before this tab existed, so flipping a deployment to read from the DB
 * is a no-op until an admin overrides them.
 */
enum AssistantSettingEnum: string implements ApplicationParameterEnumInterface
{
    case Provider = 'assistant_provider';
    case ChatModel = 'assistant_chat_model';
    case VisionModel = 'assistant_vision_model';
    case HttpTimeout = 'assistant_http_timeout';
    case NumCtx = 'assistant_num_ctx';
    case SystemPrompt = 'assistant_system_prompt';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Provider => 'backend.parameters.assistant_provider.label',
            self::ChatModel => 'backend.parameters.assistant_chat_model.label',
            self::VisionModel => 'backend.parameters.assistant_vision_model.label',
            self::HttpTimeout => 'backend.parameters.assistant_http_timeout.label',
            self::NumCtx => 'backend.parameters.assistant_num_ctx.label',
            self::SystemPrompt => 'backend.parameters.assistant_system_prompt.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Provider => 'backend.parameters.assistant_provider.description',
            self::ChatModel => 'backend.parameters.assistant_chat_model.description',
            self::VisionModel => 'backend.parameters.assistant_vision_model.description',
            self::HttpTimeout => 'backend.parameters.assistant_http_timeout.description',
            self::NumCtx => 'backend.parameters.assistant_num_ctx.description',
            self::SystemPrompt => 'backend.parameters.assistant_system_prompt.description',
        };
    }

    public function getDefaultValue(): string
    {
        // Defaults are only used by the Settings UI as a placeholder/seed
        // value. The live application reads the value through
        // {@see AssistantSettings} which falls back to env vars (closer
        // to deployment truth) when the setting is blank.
        return match ($this) {
            self::Provider => 'ollama',
            self::ChatModel => 'qwen3:8b',
            self::VisionModel => 'qwen2.5vl:3b',
            self::HttpTimeout => '300',
            self::NumCtx => '8192',
            self::SystemPrompt => AssistantDefaultPrompt::TEXT,
        };
    }

    public function getType(): string
    {
        return match ($this) {
            self::Provider => 'select',
            self::ChatModel, self::VisionModel => 'text',
            self::HttpTimeout, self::NumCtx => 'int',
            self::SystemPrompt => 'textarea',
        };
    }

    public function getGroup(): string
    {
        return 'assistant';
    }
}
