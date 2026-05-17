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
        media, projects, tasks) and files on configured filesystem mount
        points. Be concise and direct.

        Tool-use rules:
        - When the user asks for information that may exist, call the
          appropriate tool rather than guessing. Never invent IDs, paths,
          or filenames; only quote what tools return.
        - To read a file under a mount point, prefer the exact filename
          shown in directory listings. If unsure (e.g. user says "README"),
          first call filesystem_read with mode=list on the parent
          directory, then read the matching entry.
        - If a filesystem_read returns "Did you mean: …", retry once with
          the suggested name before telling the user the file is missing.
        - Use ONLY the absolute paths listed in the Filesystem mount points
          context block — never paths like /mnt/<thing> or /var/<thing>
          unless they appear there.
        - When filesystem_read refuses a file as "appears binary" and the
          extension is .png/.jpg/.jpeg/.webp/.gif, call image_read with
          the same path to get a textual description from a vision model.
        - When the user asks "find a file …" / "where is the … that
          mentions X" / "search for X", use filesystem_search (recursive
          name + content match) instead of guessing or walking the tree
          one directory at a time with filesystem_read.
        PROMPT;

    public function __construct(
        private SettingRepository $settings,
        private string $envChatModel,
        private int $envHttpTimeout,
        private int $envNumCtx,
        private string $envVisionModel,
    ) {}

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

        return null !== $value && '' !== mb_trim($value) ? $value : self::DEFAULT_SYSTEM_PROMPT;
    }
}
