<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Erp\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Erp\Service\ErpContext;
use PHPUnit\Framework\TestCase;

final class ErpContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): ErpContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new ErpContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ErpEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ErpEnabled->value => false])->isAdminEnabled());
    }

    public function testIsProductsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ErpProductsEnabled->value => true])->isProductsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ErpProductsEnabled->value => false])->isProductsEnabled());
    }
}
