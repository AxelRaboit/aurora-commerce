<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Photo\PhotoContext;
use Aurora\Module\Photo\Setting\PhotoModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class PhotoContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): PhotoContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new PhotoContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([PhotoModuleParameterEnum::Backend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([PhotoModuleParameterEnum::Backend->value => false])->isBackendEnabled());
    }

    public function testIsFrontEnabled(): void
    {
        self::assertTrue($this->makeContext([PhotoModuleParameterEnum::Frontend->value => true])->isFrontEnabled());
        self::assertFalse($this->makeContext([PhotoModuleParameterEnum::Frontend->value => false])->isFrontEnabled());
    }

    public function testIsGalleriesEnabled(): void
    {
        self::assertTrue($this->makeContext([PhotoModuleParameterEnum::Galleries->value => true])->isGalleriesEnabled());
        self::assertFalse($this->makeContext([PhotoModuleParameterEnum::Galleries->value => false])->isGalleriesEnabled());
    }
}
