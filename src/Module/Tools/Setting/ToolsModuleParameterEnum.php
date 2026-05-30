<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Tools module's own access toggles (one row each in core_settings, group
 * `modules`). Self-contained so the Tools module declares its toggles without
 * the central `ModuleParameterEnum` — that core enum no longer knows about
 * Tools (monorepo-split: each module owns its toggle definitions).
 *
 * Exposed to the toggle machinery via {@see ToolsModule}
 * (getToggles) and to the settings sync via
 * {@see ToolsModuleParameterProvider}. Stored keys are unchanged from the
 * legacy central enum (no migration / no settings wipe).
 */
enum ToolsModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_tools_backend';
    case Vault = 'modules_tools_vault';
    case PasswordGenerator = 'modules_tools_password_generator';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.tools_backend',
            self::Vault => 'backend.nav.vault',
            self::PasswordGenerator => 'backend.nav.password_generator',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.tools_backend_description',
            self::Vault => 'backend.nav.vault_description',
            self::PasswordGenerator => 'backend.nav.password_generator_description',
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
        return self::Backend === $this ? 'tools' : null;
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
