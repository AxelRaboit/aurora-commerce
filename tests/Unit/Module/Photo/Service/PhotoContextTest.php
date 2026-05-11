<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Photo\Service\PhotoContext;
use PHPUnit\Framework\TestCase;

final class PhotoContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): PhotoContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new PhotoContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PhotoBackend->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PhotoBackend->value => false])->isAdminEnabled());
    }

    public function testIsFrontEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PhotoFrontend->value => true])->isFrontEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PhotoFrontend->value => false])->isFrontEnabled());
    }

    public function testIsGalleriesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PhotoGalleries->value => true])->isGalleriesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PhotoGalleries->value => false])->isGalleriesEnabled());
    }
}
