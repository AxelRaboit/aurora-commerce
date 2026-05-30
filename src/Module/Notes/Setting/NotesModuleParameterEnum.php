<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Notes module's own access toggles (one row each in core_settings, group
 * `modules`). Self-contained so the Notes module declares its toggles without
 * the central `ModuleParameterEnum` — that core enum no longer knows about
 * Notes (monorepo-split: each module owns its toggle definitions).
 *
 * Exposed to the toggle machinery via {@see NotesModule}
 * (getToggles) and to the settings sync via
 * {@see NotesModuleParameterProvider}. Stored keys are unchanged from the
 * legacy central enum (no migration / no settings wipe).
 */
enum NotesModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_notes_backend';
    case Markdown = 'modules_notes_markdown';
    case Block = 'modules_notes_block';
    case PostIt = 'modules_notes_post_it';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.notes_backend',
            self::Markdown => 'backend.nav.notes_markdown',
            self::Block => 'backend.nav.notes_block',
            self::PostIt => 'backend.nav.notes_post_it',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.notes_backend_description',
            self::Markdown => 'backend.nav.notes_markdown_description',
            self::Block => 'backend.nav.notes_block_description',
            self::PostIt => 'backend.nav.notes_post_it_description',
        };
    }

    public function getDefaultValue(): string
    {
        return '1';
    }

    public function getType(): string
    {
        return 'bool';
    }

    public function getGroup(): string
    {
        return self::GROUP;
    }

    public function getPlaceholder(): ?string
    {
        return null;
    }

    /** Module identifier for the top-level toggle, null for sub-toggles. */
    private function getModuleId(): ?string
    {
        return self::Backend === $this ? 'notes' : null;
    }

    /** Cascade dependency (parent that must be ON), null for the top-level. */
    private function getCascadeRequires(): ?string
    {
        return self::Backend === $this ? null : self::Backend->value;
    }

    /** Structural parent for dashboard grouping, null for the top-level. */
    private function getDisplayParent(): ?string
    {
        return self::Backend === $this ? null : self::Backend->value;
    }

    public function toToggle(): ModuleToggle
    {
        return new ModuleToggle(
            key: $this->value,
            labelKey: $this->getLabel(),
            descriptionKey: $this->getDescription(),
            parentKey: $this->getCascadeRequires(),
            moduleId: $this->getModuleId(),
            displayParentKey: $this->getDisplayParent(),
        );
    }
}
