<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Setting\Enum;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class ModuleParameterEnumTest extends TestCase
{
    public function testGetKeyReturnsStringValue(): void
    {
        self::assertSame('modules_billing_backend', ModuleParameterEnum::BillingBackend->getKey());
        self::assertSame('modules_vault_backend', ModuleParameterEnum::VaultBackend->getKey());
        self::assertSame('modules_crm_backend', ModuleParameterEnum::CrmBackend->getKey());
    }

    public function testGetLabelReturnsTranslationKey(): void
    {
        self::assertSame('backend.modules.billing_backend', ModuleParameterEnum::BillingBackend->getLabel());
        self::assertSame('backend.modules.vault_backend', ModuleParameterEnum::VaultBackend->getLabel());
        self::assertSame('backend.nav.tiers', ModuleParameterEnum::BillingTiers->getLabel());
        self::assertSame('backend.nav.invoices', ModuleParameterEnum::BillingInvoices->getLabel());
    }

    public function testGetDescriptionReturnsTranslationKey(): void
    {
        self::assertSame('backend.modules.billing_backend_description', ModuleParameterEnum::BillingBackend->getDescription());
        self::assertSame('backend.modules.vault_backend_description', ModuleParameterEnum::VaultBackend->getDescription());
        self::assertSame('backend.nav.tiers_description', ModuleParameterEnum::BillingTiers->getDescription());
    }

    public function testGetDefaultValueIsOneForAllCases(): void
    {
        foreach (ModuleParameterEnum::cases() as $case) {
            self::assertSame('1', $case->getDefaultValue(), sprintf('%s should default to "1"', $case->name));
        }
    }

    public function testGetTypeIsBoolForAllCases(): void
    {
        foreach (ModuleParameterEnum::cases() as $case) {
            self::assertSame('bool', $case->getType(), sprintf('%s should have type "bool"', $case->name));
        }
    }

    public function testGetGroupReturnsModuleConstantForAllCases(): void
    {
        foreach (ModuleParameterEnum::cases() as $case) {
            self::assertSame(ModuleParameterEnum::MODULE, $case->getGroup(), sprintf('%s should be in MODULE group', $case->name));
        }
    }

    public function testGetCascadeRequiresInterModuleDependencies(): void
    {
        self::assertSame(ModuleParameterEnum::CrmBackend->value, ModuleParameterEnum::ErpBackend->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::ErpBackend->value, ModuleParameterEnum::EcommerceBackend->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::CrmBackend->value, ModuleParameterEnum::BillingBackend->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::PhotoBackend->value, ModuleParameterEnum::PhotoFrontend->getCascadeRequires());
    }

    public function testGetCascadeRequiresIntraModuleDependencies(): void
    {
        self::assertSame(ModuleParameterEnum::BillingTiers->value, ModuleParameterEnum::BillingInvoices->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::CrmContacts->value, ModuleParameterEnum::CrmDeals->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::EcommerceListings->value, ModuleParameterEnum::EcommerceOrders->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::EditorialPostTypes->value, ModuleParameterEnum::EditorialTaxonomies->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::EditorialPosts->value, ModuleParameterEnum::EditorialComments->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::EditorialPosts->value, ModuleParameterEnum::EditorialSitemap->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::VaultBackend->value, ModuleParameterEnum::VaultSafe->getCascadeRequires());
    }

    public function testGetCascadeRequiresNullForTopLevelWithoutDependency(): void
    {
        self::assertNull(ModuleParameterEnum::EditorialBackend->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::CrmBackend->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::GedBackend->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::HrBackend->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::PlanningBackend->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::ProjectBackend->getCascadeRequires());
    }

    public function testGetCascadeDisableTargetsCrmEnabled(): void
    {
        $targets = ModuleParameterEnum::CrmBackend->getCascadeDisableTargets();

        self::assertContains(ModuleParameterEnum::ErpBackend->value, $targets);
        self::assertContains(ModuleParameterEnum::EcommerceBackend->value, $targets);
        self::assertContains(ModuleParameterEnum::EcommerceFrontend->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingBackend->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingTiers->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingInvoices->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingCompliance->value, $targets);
    }

    public function testGetCascadeDisableTargetsBillingEnabled(): void
    {
        $targets = ModuleParameterEnum::BillingBackend->getCascadeDisableTargets();

        self::assertContains(ModuleParameterEnum::BillingTiers->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingInvoices->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingCompliance->value, $targets);
    }

    public function testGetCascadeDisableTargetsVaultEnabled(): void
    {
        $targets = ModuleParameterEnum::VaultBackend->getCascadeDisableTargets();

        self::assertContains(ModuleParameterEnum::VaultSafe->value, $targets);
        self::assertContains(ModuleParameterEnum::VaultPasswordGenerator->value, $targets);
    }

    public function testGetCascadeDisableTargetsEditorialEnabled(): void
    {
        $targets = ModuleParameterEnum::EditorialBackend->getCascadeDisableTargets();

        self::assertContains(ModuleParameterEnum::EditorialPosts->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialMenus->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialPostTypes->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialTaxonomies->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialComments->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialForms->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialSitemap->value, $targets);
    }

    public function testGetParentCaseForTopLevelReturnsNull(): void
    {
        self::assertNull(ModuleParameterEnum::BillingBackend->getParentCase());
        self::assertNull(ModuleParameterEnum::CrmBackend->getParentCase());
        self::assertNull(ModuleParameterEnum::EditorialBackend->getParentCase());
        self::assertNull(ModuleParameterEnum::VaultBackend->getParentCase());
    }

    public function testGetParentCaseForSubModules(): void
    {
        self::assertSame(ModuleParameterEnum::BillingBackend, ModuleParameterEnum::BillingInvoices->getParentCase());
        self::assertSame(ModuleParameterEnum::CrmBackend, ModuleParameterEnum::CrmDeals->getParentCase());
        self::assertSame(ModuleParameterEnum::EditorialBackend, ModuleParameterEnum::EditorialTaxonomies->getParentCase());
        self::assertSame(ModuleParameterEnum::VaultBackend, ModuleParameterEnum::VaultSafe->getParentCase());
        self::assertSame(ModuleParameterEnum::VaultBackend, ModuleParameterEnum::VaultPasswordGenerator->getParentCase());
    }

    public function testGetModuleIdForTopLevelEnabledCases(): void
    {
        self::assertSame('vault', ModuleParameterEnum::VaultBackend->getModuleId());
        self::assertSame('billing', ModuleParameterEnum::BillingBackend->getModuleId());
        self::assertSame('crm', ModuleParameterEnum::CrmBackend->getModuleId());
        self::assertSame('editorial', ModuleParameterEnum::EditorialBackend->getModuleId());
        self::assertSame('erp', ModuleParameterEnum::ErpBackend->getModuleId());
        self::assertSame('ecommerce', ModuleParameterEnum::EcommerceBackend->getModuleId());
        self::assertSame('photo', ModuleParameterEnum::PhotoBackend->getModuleId());
    }

    public function testGetModuleIdReturnsNullForSubModules(): void
    {
        self::assertNull(ModuleParameterEnum::BillingInvoices->getModuleId());
        self::assertNull(ModuleParameterEnum::CrmDeals->getModuleId());
        self::assertNull(ModuleParameterEnum::VaultSafe->getModuleId());
        self::assertNull(ModuleParameterEnum::EditorialPosts->getModuleId());
        self::assertNull(ModuleParameterEnum::EcommerceFrontend->getModuleId());
        self::assertNull(ModuleParameterEnum::PhotoFrontend->getModuleId());
    }
}
