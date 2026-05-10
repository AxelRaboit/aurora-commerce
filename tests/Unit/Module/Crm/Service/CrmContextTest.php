<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Crm\Service\CrmContext;
use PHPUnit\Framework\TestCase;

final class CrmContextTest extends TestCase
{
    private function makeContext(array $values): CrmContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new CrmContext($repository);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::CrmEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::CrmEnabled->value => false])->isAdminEnabled());
    }

    public function testIsContactsEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::CrmEnabled->value => true,
            ModuleParameterEnum::CrmContactsEnabled->value => true,
        ]);
        self::assertTrue($context->isContactsEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::CrmEnabled->value => false,
            ModuleParameterEnum::CrmContactsEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isContactsEnabled());
    }

    public function testIsCompaniesEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::CrmEnabled->value => true,
            ModuleParameterEnum::CrmCompaniesEnabled->value => true,
        ]);
        self::assertTrue($context->isCompaniesEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::CrmEnabled->value => false,
            ModuleParameterEnum::CrmCompaniesEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isCompaniesEnabled());
    }

    public function testIsDealsEnabledRequiresContacts(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::CrmEnabled->value => true,
            ModuleParameterEnum::CrmContactsEnabled->value => true,
            ModuleParameterEnum::CrmDealsEnabled->value => true,
        ]);
        self::assertTrue($context->isDealsEnabled());

        $contextContactsOff = $this->makeContext([
            ModuleParameterEnum::CrmEnabled->value => true,
            ModuleParameterEnum::CrmContactsEnabled->value => false,
            ModuleParameterEnum::CrmDealsEnabled->value => true,
        ]);
        self::assertFalse($contextContactsOff->isDealsEnabled());
    }
}
