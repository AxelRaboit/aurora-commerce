<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ged\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Ged\Service\GedContext;
use PHPUnit\Framework\TestCase;

final class GedContextTest extends TestCase
{
    private function makeContext(array $values): GedContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new GedContext($repository);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::GedEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::GedEnabled->value => false])->isAdminEnabled());
    }

    public function testIsDocumentsEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::GedEnabled->value => true,
            ModuleParameterEnum::GedDocumentsEnabled->value => true,
        ]);
        self::assertTrue($context->isDocumentsEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::GedEnabled->value => false,
            ModuleParameterEnum::GedDocumentsEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isDocumentsEnabled());
    }

    public function testIsCategoriesEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::GedEnabled->value => true,
            ModuleParameterEnum::GedCategoriesEnabled->value => true,
        ]);
        self::assertTrue($context->isCategoriesEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::GedEnabled->value => false,
            ModuleParameterEnum::GedCategoriesEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isCategoriesEnabled());
    }
}
