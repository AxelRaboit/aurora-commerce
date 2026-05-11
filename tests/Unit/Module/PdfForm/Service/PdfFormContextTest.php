<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\Service;

use Aurora\Core\Module\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\PdfForm\Service\PdfFormContext;
use PHPUnit\Framework\TestCase;

final class PdfFormContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): PdfFormContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new PdfFormContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PdfFormEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PdfFormEnabled->value => false])->isAdminEnabled());
    }

    public function testIsTemplatesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PdfFormTemplatesEnabled->value => true])->isTemplatesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PdfFormTemplatesEnabled->value => false])->isTemplatesEnabled());
    }

    public function testIsDocumentsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PdfFormDocumentsEnabled->value => true])->isDocumentsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PdfFormDocumentsEnabled->value => false])->isDocumentsEnabled());
    }
}
