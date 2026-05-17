<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Crm\CrmContext;
use PHPUnit\Framework\TestCase;

final class CrmContextTest extends TestCase
{
    /** @param array<string, bool> $values mapping ModuleParameterEnum::value => isEnabled() outcome */
    private function makeContext(array $values): CrmContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new CrmContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::CrmBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::CrmBackend->value => false])->isBackendEnabled());
    }

    public function testIsContactsEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::CrmContacts->value => true])->isContactsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::CrmContacts->value => false])->isContactsEnabled());
    }

    public function testIsCompaniesEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::CrmCompanies->value => true])->isCompaniesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::CrmCompanies->value => false])->isCompaniesEnabled());
    }

    public function testIsDealsEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::CrmDeals->value => true])->isDealsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::CrmDeals->value => false])->isDealsEnabled());
    }
}
