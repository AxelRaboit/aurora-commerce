<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Erp\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Erp\Service\ErpContext;
use PHPUnit\Framework\TestCase;

final class ErpContextTest extends TestCase
{
    private function makeContext(array $values): ErpContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new ErpContext($repository);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ErpEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ErpEnabled->value => false])->isAdminEnabled());
    }

    public function testIsProductsEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::ErpEnabled->value => true,
            ModuleParameterEnum::ErpProductsEnabled->value => true,
        ]);
        self::assertTrue($context->isProductsEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::ErpEnabled->value => false,
            ModuleParameterEnum::ErpProductsEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isProductsEnabled());
    }
}
