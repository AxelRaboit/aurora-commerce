<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Project module's own access toggles (one row each in core_settings, group
 * `modules`). Self-contained so the Project module declares its toggles without
 * the central `ModuleParameterEnum` — that core enum no longer knows about
 * Project (monorepo-split: each module owns its toggle definitions).
 *
 * Exposed to the toggle machinery via {@see ProjectModule}
 * (getToggles) and to the settings sync via
 * {@see ProjectModuleParameterProvider}. Stored keys are unchanged from the
 * legacy central enum (no migration / no settings wipe).
 */
enum ProjectModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_project_backend';
    case Projects = 'modules_project_projects';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.project_backend',
            self::Projects => 'backend.nav.projects',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.project_backend_description',
            self::Projects => 'backend.nav.projects_description',
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
        return self::Backend === $this ? 'project' : null;
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
