<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Setting;

use Aurora\Core\Setting\Repository\SettingRepository;

/**
 * Reads Assistant runtime config with a two-step fallback chain :
 *   1. Setting persisted in `core_settings` (set via /backend/settings)
 *   2. Env var wired in services.yaml (deployment baseline)
 *
 * Splitting this out of {@see OllamaChatClient} keeps the chat client
 * pure HTTP and lets the manager + future tools share the same prompt
 * source. Methods are called per request, so a settings change applies
 * to the next turn without restarting PHP.
 */
final readonly class AssistantSettings
{
    private const string DEFAULT_SYSTEM_PROMPT = <<<'PROMPT'
        You are Aurora's in-app assistant. You help the signed-in user navigate
        and reason about their own Aurora workspace (posts, taxonomy terms,
        media, projects, tasks). Be concise and direct. When the user asks for
        information that may exist in Aurora, call the appropriate tool rather
        than guessing. Never invent IDs, references, or filenames; only quote
        what tools return.
        PROMPT;

    public function __construct(
        private SettingRepository $settings,
        private string $envChatModel,
        private int $envHttpTimeout,
        private int $envNumCtx,
    ) {}

    public function getChatModel(): string
    {
        $value = $this->settings->get(AssistantSettingEnum::ChatModel->value);

        return null !== $value && '' !== $value ? $value : $this->envChatModel;
    }

    public function getHttpTimeout(): int
    {
        $value = $this->settings->get(AssistantSettingEnum::HttpTimeout->value);

        return null !== $value && '' !== $value ? (int) $value : $this->envHttpTimeout;
    }

    public function getNumCtx(): int
    {
        $value = $this->settings->get(AssistantSettingEnum::NumCtx->value);

        return null !== $value && '' !== $value ? (int) $value : $this->envNumCtx;
    }

    public function getSystemPrompt(): string
    {
        $value = $this->settings->get(AssistantSettingEnum::SystemPrompt->value);

        return null !== $value && '' !== mb_trim($value) ? $value : self::DEFAULT_SYSTEM_PROMPT;
    }
}
