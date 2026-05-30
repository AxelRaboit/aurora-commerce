<?php

declare(strict_types=1);

namespace Aurora\Module\Crm\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;
use Aurora\Module\Crm\CrmModule;

/**
 * Crm module's own access toggles (one row each in core_settings, group
 * `modules`). Self-contained so the Crm module declares its toggles without
 * the central `ModuleParameterEnum` — that core enum no longer knows about
 * Crm (monorepo-split: each module owns its toggle definitions).
 *
 * Exposed to the toggle machinery via {@see CrmModule}
 * (getToggles) and to the settings sync via
 * {@see CrmModuleParameterProvider}. Stored keys are unchanged from the
 * legacy central enum (no migration / no settings wipe).
 */
enum CrmModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_crm_backend';
    case Contacts = 'modules_crm_contacts';
    case Companies = 'modules_crm_companies';
    case Deals = 'modules_crm_deals';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.crm_backend',
            self::Contacts => 'backend.nav.contacts',
            self::Companies => 'backend.nav.companies',
            self::Deals => 'backend.nav.deals',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.crm_backend_description',
            self::Contacts => 'backend.nav.contacts_description',
            self::Companies => 'backend.nav.companies_description',
            self::Deals => 'backend.nav.deals_description',
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
        return self::Backend === $this ? 'crm' : null;
    }

    /**
     * Cascade dependency (parent that must be ON), null for the top-level.
     * Cross-module cascades stay as plain strings (no import of other modules).
     */
    private function getCascadeRequires(): ?string
    {
        return match ($this) {
            self::Contacts => self::Backend->value,
            self::Companies => self::Backend->value,
            self::Deals => self::Contacts->value,
            self::Backend => null,
        };
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
