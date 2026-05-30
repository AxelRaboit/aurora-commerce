<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Billing module's own access toggles (one row each in core_settings, group
 * `modules`). Self-contained so the Billing module declares its toggles without
 * the central `ModuleParameterEnum` — that core enum no longer knows about
 * Billing (monorepo-split: each module owns its toggle definitions).
 *
 * Exposed to the toggle machinery via {@see BillingModule}
 * (getToggles) and to the settings sync via
 * {@see BillingModuleParameterProvider}. Stored keys are unchanged from the
 * legacy central enum (no migration / no settings wipe).
 */
enum BillingModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_billing_backend';
    case Tiers = 'modules_billing_tiers';
    case Invoices = 'modules_billing_invoices';
    case Compliance = 'modules_billing_compliance';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.billing_backend',
            self::Tiers => 'backend.nav.tiers',
            self::Invoices => 'backend.nav.invoices',
            self::Compliance => 'backend.nav.ocr_import',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.billing_backend_description',
            self::Tiers => 'backend.nav.tiers_description',
            self::Invoices => 'backend.nav.invoices_description',
            self::Compliance => 'backend.nav.ocr_import_description',
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
        return self::Backend === $this ? 'billing' : null;
    }

    /**
     * Cascade dependency (parent that must be ON). Every case requires a
     * parent: Backend requires the CRM module's backend — a cross-module
     * dependency kept as a plain key string (no import on the central enum).
     */
    private function getCascadeRequires(): string
    {
        return match ($this) {
            self::Backend => 'modules_crm_backend',
            self::Tiers => self::Backend->value,
            self::Invoices => self::Tiers->value,
            self::Compliance => self::Backend->value,
        };
    }

    /** Structural parent for dashboard grouping, null for the top-level. */
    private function getParentCase(): ?self
    {
        return match ($this) {
            self::Tiers, self::Invoices, self::Compliance => self::Backend,
            self::Backend => null,
        };
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
