<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Crm\Service\CrmContext;
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
        self::assertTrue($this->makeContext([ModuleParameterEnum::CrmEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::CrmEnabled->value => false])->isAdminEnabled());
    }

    public function testIsContactsEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::CrmContactsEnabled->value => true])->isContactsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::CrmContactsEnabled->value => false])->isContactsEnabled());
    }

    public function testIsCompaniesEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::CrmCompaniesEnabled->value => true])->isCompaniesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::CrmCompaniesEnabled->value => false])->isCompaniesEnabled());
    }

    public function testIsDealsEnabledDelegatesToChecker(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::CrmDealsEnabled->value => true])->isDealsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::CrmDealsEnabled->value => false])->isDealsEnabled());
    }
}
