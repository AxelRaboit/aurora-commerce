<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PdfForm\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
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
        self::assertTrue($this->makeContext([ModuleParameterEnum::PdfFormBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PdfFormBackend->value => false])->isBackendEnabled());
    }

    public function testIsTemplatesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PdfFormTemplates->value => true])->isTemplatesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PdfFormTemplates->value => false])->isTemplatesEnabled());
    }

    public function testIsDocumentsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PdfFormDocuments->value => true])->isDocumentsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PdfFormDocuments->value => false])->isDocumentsEnabled());
    }
}
