<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Erp\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Erp\ErpContext;
use Aurora\Module\Erp\Setting\ErpModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class ErpContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): ErpContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new ErpContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ErpModuleParameterEnum::Backend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ErpModuleParameterEnum::Backend->value => false])->isBackendEnabled());
    }

    public function testIsProductsEnabled(): void
    {
        self::assertTrue($this->makeContext([ErpModuleParameterEnum::Products->value => true])->isProductsEnabled());
        self::assertFalse($this->makeContext([ErpModuleParameterEnum::Products->value => false])->isProductsEnabled());
    }
}
