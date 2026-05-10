<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Photo\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Photo\Service\PhotoContext;
use PHPUnit\Framework\TestCase;

final class PhotoContextTest extends TestCase
{
    private function makeContext(array $values): PhotoContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new PhotoContext($repository);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PhotoEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PhotoEnabled->value => false])->isAdminEnabled());
    }

    public function testIsFrontEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::PhotoPublicEnabled->value => true])->isFrontEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::PhotoPublicEnabled->value => false])->isFrontEnabled());
    }

    public function testIsGalleriesEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::PhotoEnabled->value => true,
            ModuleParameterEnum::PhotoGalleriesEnabled->value => true,
        ]);
        self::assertTrue($context->isGalleriesEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::PhotoEnabled->value => false,
            ModuleParameterEnum::PhotoGalleriesEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isGalleriesEnabled());
    }
}
