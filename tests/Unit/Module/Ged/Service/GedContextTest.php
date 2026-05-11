<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Ged\Service\GedContext;
use PHPUnit\Framework\TestCase;

final class GedContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): GedContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new GedContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::GedEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::GedEnabled->value => false])->isAdminEnabled());
    }

    public function testIsDocumentsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::GedDocumentsEnabled->value => true])->isDocumentsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::GedDocumentsEnabled->value => false])->isDocumentsEnabled());
    }

    public function testIsCategoriesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::GedCategoriesEnabled->value => true])->isCategoriesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::GedCategoriesEnabled->value => false])->isCategoriesEnabled());
    }
}
