<?php

declare(strict_types=1);

namespace Aurora\Module\Assistant\Setting;

use Aurora\Core\Configuration\Setting\Repository\SettingRepository;

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
    public function __construct(
        private SettingRepository $settings,
        private string $envChatModel,
        private int $envHttpTimeout,
        private int $envNumCtx,
        private string $envVisionModel,
        private string $envProvider,
    ) {}

    public function getProvider(): string
    {
        $value = $this->settings->get(AssistantSettingEnum::Provider->value);

        return (null !== $value && '' !== $value) ? $value : $this->envProvider;
    }

    public function getVisionModel(): string
    {
        $value = $this->settings->get(AssistantSettingEnum::VisionModel->value);

        return null !== $value && '' !== $value ? $value : $this->envVisionModel;
    }

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

        return null !== $value && '' !== mb_trim($value) ? $value : AssistantDefaultPrompt::TEXT;
    }
}
