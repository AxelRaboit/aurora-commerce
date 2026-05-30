<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Crm\CrmContext;
use Aurora\Module\Crm\Setting\CrmModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class CrmContextTest extends TestCase
{
    /** @param array<string, bool> $values mapping CrmModuleParameterEnum::value => isEnabled() outcome */
    private function makeContext(array $values): CrmContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new CrmContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([CrmModuleParameterEnum::Backend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([CrmModuleParameterEnum::Backend->value => false])->isBackendEnabled());
    }

    public function testIsContactsEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([CrmModuleParameterEnum::Contacts->value => true])->isContactsEnabled());
        self::assertFalse($this->makeContext([CrmModuleParameterEnum::Contacts->value => false])->isContactsEnabled());
    }

    public function testIsCompaniesEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([CrmModuleParameterEnum::Companies->value => true])->isCompaniesEnabled());
        self::assertFalse($this->makeContext([CrmModuleParameterEnum::Companies->value => false])->isCompaniesEnabled());
    }

    public function testIsDealsEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([CrmModuleParameterEnum::Deals->value => true])->isDealsEnabled());
        self::assertFalse($this->makeContext([CrmModuleParameterEnum::Deals->value => false])->isDealsEnabled());
    }
}
