<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Setting;

use Aurora\Core\Module\Toggle\ModuleToggle;
use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnumInterface;
use Aurora\Module\Ecommerce\EcommerceModule;

/**
 * Ecommerce module's own access toggles (one row each in core_settings, group
 * `modules`). Self-contained so the Ecommerce module declares its toggles
 * without the central `ModuleParameterEnum` — that core enum no longer knows
 * about Ecommerce (monorepo-split: each module owns its toggle definitions).
 *
 * Exposed to the toggle machinery via {@see EcommerceModule}
 * (getToggles) and to the settings sync via
 * {@see EcommerceModuleParameterProvider}. Stored keys are unchanged from the
 * legacy central enum (no migration / no settings wipe).
 *
 * Cross-module / display-parent intricacies preserved exactly from the central
 * enum:
 *  - Backend depends on `modules_erp_backend` (cross-module cascade), is the
 *    module root (moduleId `ecommerce`), no display parent.
 *  - Frontend depends on `modules_erp_backend` too, but is a standalone
 *    top-level toggle: no moduleId, no display parent.
 *  - Listings cascades from Backend, displayed under Backend.
 *  - Orders cascades from Listings, but is displayed under Backend (its
 *    structural/grouping parent), not under Listings.
 */
enum EcommerceModuleParameterEnum: string implements ApplicationParameterEnumInterface
{
    private const string GROUP = 'modules';

    case Backend = 'modules_ecommerce_backend';
    case Frontend = 'modules_ecommerce_frontend';
    case Listings = 'modules_ecommerce_listings';
    case Orders = 'modules_ecommerce_orders';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.ecommerce_backend',
            self::Frontend => 'backend.modules.ecommerce_frontend',
            self::Listings => 'backend.nav.listings',
            self::Orders => 'backend.nav.orders',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Backend => 'backend.modules.ecommerce_backend_description',
            self::Frontend => 'backend.modules.ecommerce_frontend_description',
            self::Listings => 'backend.nav.listings_description',
            self::Orders => 'backend.nav.orders_description',
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

    /** Module identifier for the module-root toggle, null otherwise. */
    private function getModuleId(): ?string
    {
        return self::Backend === $this ? 'ecommerce' : null;
    }

    /**
     * Cascade dependency (key of the parameter that must be active before this
     * one can be enabled). Backend and Frontend both require the ERP backend
     * (cross-module); the sub-modules chain Backend → Listings → Orders.
     */
    private function getCascadeRequires(): ?string
    {
        return match ($this) {
            self::Backend, self::Frontend => 'modules_erp_backend',
            self::Listings => self::Backend->value,
            self::Orders => self::Listings->value,
        };
    }

    /**
     * Structural parent for dashboard grouping, null for top-level toggles.
     * Both Listings and Orders are grouped under Backend (Orders' display
     * parent is Backend, not its cascade parent Listings).
     */
    private function getDisplayParent(): ?string
    {
        return match ($this) {
            self::Listings, self::Orders => self::Backend->value,
            self::Backend, self::Frontend => null,
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
            displayParentKey: $this->getDisplayParent(),
        );
    }
}
