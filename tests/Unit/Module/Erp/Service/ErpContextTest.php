<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Erp\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Erp\ErpContext;
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
        self::assertTrue($this->makeContext([ModuleParameterEnum::ErpBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ErpBackend->value => false])->isBackendEnabled());
    }

    public function testIsProductsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::ErpProducts->value => true])->isProductsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::ErpProducts->value => false])->isProductsEnabled());
    }
}
