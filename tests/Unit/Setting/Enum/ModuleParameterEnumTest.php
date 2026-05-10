<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Setting\Enum;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class ModuleParameterEnumTest extends TestCase
{
    public function testGetKeyReturnsStringValue(): void
    {
        self::assertSame('backend_billing_admin', ModuleParameterEnum::BillingEnabled->getKey());
        self::assertSame('backend_vault_admin', ModuleParameterEnum::VaultEnabled->getKey());
        self::assertSame('backend_crm_admin', ModuleParameterEnum::CrmEnabled->getKey());
    }

    public function testGetLabelReturnsTranslationKey(): void
    {
        self::assertSame('backend.modules.billing', ModuleParameterEnum::BillingEnabled->getLabel());
        self::assertSame('backend.modules.vault', ModuleParameterEnum::VaultEnabled->getLabel());
        self::assertSame('backend.nav.tiers', ModuleParameterEnum::BillingTiersEnabled->getLabel());
        self::assertSame('backend.nav.invoices', ModuleParameterEnum::BillingInvoicesEnabled->getLabel());
    }

    public function testGetDescriptionReturnsTranslationKey(): void
    {
        self::assertSame('backend.modules.billing_description', ModuleParameterEnum::BillingEnabled->getDescription());
        self::assertSame('backend.modules.vault_description', ModuleParameterEnum::VaultEnabled->getDescription());
        self::assertSame('backend.nav.tiers_description', ModuleParameterEnum::BillingTiersEnabled->getDescription());
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
        self::assertSame(ModuleParameterEnum::CrmEnabled->value, ModuleParameterEnum::ErpEnabled->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::ErpEnabled->value, ModuleParameterEnum::EcommerceEnabled->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::CrmEnabled->value, ModuleParameterEnum::BillingEnabled->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::PhotoEnabled->value, ModuleParameterEnum::PhotoPublicEnabled->getCascadeRequires());
    }

    public function testGetCascadeRequiresIntraModuleDependencies(): void
    {
        self::assertSame(ModuleParameterEnum::BillingTiersEnabled->value, ModuleParameterEnum::BillingInvoicesEnabled->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::CrmContactsEnabled->value, ModuleParameterEnum::CrmDealsEnabled->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::EcommerceListingsEnabled->value, ModuleParameterEnum::EcommerceOrdersEnabled->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::EditorialPostTypesEnabled->value, ModuleParameterEnum::EditorialTaxonomiesEnabled->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::EditorialPostsEnabled->value, ModuleParameterEnum::EditorialCommentsEnabled->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::EditorialPostsEnabled->value, ModuleParameterEnum::EditorialSitemapEnabled->getCascadeRequires());
        self::assertSame(ModuleParameterEnum::VaultEnabled->value, ModuleParameterEnum::VaultSafeEnabled->getCascadeRequires());
    }

    public function testGetCascadeRequiresNullForTopLevelWithoutDependency(): void
    {
        self::assertNull(ModuleParameterEnum::EditorialEnabled->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::CrmEnabled->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::GedEnabled->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::HrEnabled->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::PlanningEnabled->getCascadeRequires());
        self::assertNull(ModuleParameterEnum::ProjectEnabled->getCascadeRequires());
    }

    public function testGetCascadeDisableTargetsCrmEnabled(): void
    {
        $targets = ModuleParameterEnum::CrmEnabled->getCascadeDisableTargets();

        self::assertContains(ModuleParameterEnum::ErpEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::EcommerceEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::EcommerceShopEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingTiersEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingInvoicesEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingComplianceEnabled->value, $targets);
    }

    public function testGetCascadeDisableTargetsBillingEnabled(): void
    {
        $targets = ModuleParameterEnum::BillingEnabled->getCascadeDisableTargets();

        self::assertContains(ModuleParameterEnum::BillingTiersEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingInvoicesEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::BillingComplianceEnabled->value, $targets);
    }

    public function testGetCascadeDisableTargetsVaultEnabled(): void
    {
        $targets = ModuleParameterEnum::VaultEnabled->getCascadeDisableTargets();

        self::assertContains(ModuleParameterEnum::VaultSafeEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::VaultPasswordGeneratorEnabled->value, $targets);
    }

    public function testGetCascadeDisableTargetsEditorialEnabled(): void
    {
        $targets = ModuleParameterEnum::EditorialEnabled->getCascadeDisableTargets();

        self::assertContains(ModuleParameterEnum::EditorialPostsEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialMenusEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialPostTypesEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialTaxonomiesEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialCommentsEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialFormsEnabled->value, $targets);
        self::assertContains(ModuleParameterEnum::EditorialSitemapEnabled->value, $targets);
    }

    public function testGetParentCaseForTopLevelReturnsNull(): void
    {
        self::assertNull(ModuleParameterEnum::BillingEnabled->getParentCase());
        self::assertNull(ModuleParameterEnum::CrmEnabled->getParentCase());
        self::assertNull(ModuleParameterEnum::EditorialEnabled->getParentCase());
        self::assertNull(ModuleParameterEnum::VaultEnabled->getParentCase());
    }

    public function testGetParentCaseForSubModules(): void
    {
        self::assertSame(ModuleParameterEnum::BillingEnabled, ModuleParameterEnum::BillingInvoicesEnabled->getParentCase());
        self::assertSame(ModuleParameterEnum::CrmEnabled, ModuleParameterEnum::CrmDealsEnabled->getParentCase());
        self::assertSame(ModuleParameterEnum::EditorialEnabled, ModuleParameterEnum::EditorialTaxonomiesEnabled->getParentCase());
        self::assertSame(ModuleParameterEnum::VaultEnabled, ModuleParameterEnum::VaultSafeEnabled->getParentCase());
        self::assertSame(ModuleParameterEnum::VaultEnabled, ModuleParameterEnum::VaultPasswordGeneratorEnabled->getParentCase());
    }

    public function testGetModuleIdForTopLevelEnabledCases(): void
    {
        self::assertSame('vault', ModuleParameterEnum::VaultEnabled->getModuleId());
        self::assertSame('billing', ModuleParameterEnum::BillingEnabled->getModuleId());
        self::assertSame('crm', ModuleParameterEnum::CrmEnabled->getModuleId());
        self::assertSame('editorial', ModuleParameterEnum::EditorialEnabled->getModuleId());
        self::assertSame('erp', ModuleParameterEnum::ErpEnabled->getModuleId());
        self::assertSame('ecommerce', ModuleParameterEnum::EcommerceEnabled->getModuleId());
        self::assertSame('photo', ModuleParameterEnum::PhotoEnabled->getModuleId());
    }

    public function testGetModuleIdReturnsNullForSubModules(): void
    {
        self::assertNull(ModuleParameterEnum::BillingInvoicesEnabled->getModuleId());
        self::assertNull(ModuleParameterEnum::CrmDealsEnabled->getModuleId());
        self::assertNull(ModuleParameterEnum::VaultSafeEnabled->getModuleId());
        self::assertNull(ModuleParameterEnum::EditorialPostsEnabled->getModuleId());
        self::assertNull(ModuleParameterEnum::EcommerceShopEnabled->getModuleId());
        self::assertNull(ModuleParameterEnum::PhotoPublicEnabled->getModuleId());
    }
}
