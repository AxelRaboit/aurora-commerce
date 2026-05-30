<?php

declare(strict_types=1);

namespace Aurora\Module\Planning\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Planning module's own access toggles (one row each in core_settings, group
 * `modules`). Self-contained so the Planning module declares its toggles
 * without the central `ModuleParameterEnum` — that core enum no longer knows
 * about Planning (monorepo-split: each module owns its toggle definitions).
 *
 * Exposed to the toggle machinery via
 * {@see PlanningModule} (getToggles) and to the
 * settings sync via {@see PlanningModuleParameterProvider}. Stored keys are
 * unchanged from the legacy central enum (no migration / no settings wipe).
 */
enum PlanningModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_planning_backend';
    case Plannings = 'modules_planning_plannings';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.planning_backend',
            self::Plannings => 'backend.nav.plannings',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.planning_backend_description',
            self::Plannings => 'backend.nav.plannings_description',
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
        return self::Backend === $this ? 'planning' : null;
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
