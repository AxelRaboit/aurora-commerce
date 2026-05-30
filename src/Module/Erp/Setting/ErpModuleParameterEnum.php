<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;
use Aurora\Module\Erp\ErpModule;

/**
 * ERP module's own access toggles (one row each in core_settings, group
 * `modules`). Self-contained so the Erp module declares its toggles without
 * the central `ModuleParameterEnum` — that core enum no longer knows about
 * Erp (monorepo-split: each module owns its toggle definitions).
 *
 * Exposed to the toggle machinery via {@see ErpModule}
 * (getToggles) and to the settings sync via {@see ErpModuleParameterProvider}.
 * Stored keys are unchanged from the legacy central enum (no migration / no
 * settings wipe).
 *
 * Note: the top-level `Backend` toggle has a CROSS-MODULE cascade dependency on
 * the CRM module (`modules_crm_backend`), kept as a plain string to avoid
 * coupling the Erp enum to the Crm enum.
 */
enum ErpModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_erp_backend';
    case Products = 'modules_erp_products';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.erp_backend',
            self::Products => 'backend.nav.products',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.erp_backend_description',
            self::Products => 'backend.nav.products_description',
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
        return self::Backend === $this ? 'erp' : null;
    }

    /**
     * Cascade dependency (parent that must be ON), null for the top-level.
     * Backend depends on the CRM module (cross-module, plain string).
     */
    private function getCascadeRequires(): ?string
    {
        return match ($this) {
            self::Backend => 'modules_crm_backend',
            self::Products => self::Backend->value,
        };
    }

    /** Structural parent for dashboard grouping, null for the top-level. */
    private function getParentCase(): ?self
    {
        return self::Products === $this ? self::Backend : null;
    }

    public function toToggle(): ModuleToggle
    {
        return new ModuleToggle(
            key: $this->value,
            labelKey: $this->getLabel(),
            descriptionKey: $this->getDescription(),
            parentKey: $this->getCascadeRequires(),
            moduleId: $this->getModuleId(),
            displayParentKey: $this->getParentCase()?->value,
        );
    }
}
