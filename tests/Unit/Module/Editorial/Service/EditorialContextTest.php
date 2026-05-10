<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Service;

use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Service\EditorialContext;
use PHPUnit\Framework\TestCase;

final class EditorialContextTest extends TestCase
{
    private function makeContext(array $values): EditorialContext
    {
        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getBoolean')->willReturnCallback(
            static fn (string $key, bool $default): bool => array_key_exists($key, $values)
                ? $values[$key]
                : $default,
        );

        return new EditorialContext($repository);
    }

    private function allEnabled(): array
    {
        return [
            ModuleParameterEnum::EditorialEnabled->value => true,
            ModuleParameterEnum::EditorialPostsEnabled->value => true,
            ModuleParameterEnum::EditorialMenusEnabled->value => true,
            ModuleParameterEnum::EditorialPostTypesEnabled->value => true,
            ModuleParameterEnum::EditorialTaxonomiesEnabled->value => true,
            ModuleParameterEnum::EditorialCommentsEnabled->value => true,
            ModuleParameterEnum::EditorialFormsEnabled->value => true,
            ModuleParameterEnum::EditorialSitemapEnabled->value => true,
        ];
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialEnabled->value => false])->isAdminEnabled());
    }

    public function testIsPostsEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::EditorialEnabled->value => true,
            ModuleParameterEnum::EditorialPostsEnabled->value => true,
        ]);
        self::assertTrue($context->isPostsEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::EditorialEnabled->value => false,
            ModuleParameterEnum::EditorialPostsEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isPostsEnabled());
    }

    public function testIsMenusEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::EditorialEnabled->value => true,
            ModuleParameterEnum::EditorialMenusEnabled->value => true,
        ]);
        self::assertTrue($context->isMenusEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::EditorialEnabled->value => false,
            ModuleParameterEnum::EditorialMenusEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isMenusEnabled());
    }

    public function testIsPostTypesEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::EditorialEnabled->value => true,
            ModuleParameterEnum::EditorialPostTypesEnabled->value => true,
        ]);
        self::assertTrue($context->isPostTypesEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::EditorialEnabled->value => false,
            ModuleParameterEnum::EditorialPostTypesEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isPostTypesEnabled());
    }

    public function testIsTaxonomiesEnabledRequiresPostTypes(): void
    {
        $context = $this->makeContext($this->allEnabled());
        self::assertTrue($context->isTaxonomiesEnabled());

        $contextPostTypesOff = $this->makeContext(array_merge($this->allEnabled(), [
            ModuleParameterEnum::EditorialPostTypesEnabled->value => false,
        ]));
        self::assertFalse($contextPostTypesOff->isTaxonomiesEnabled());
    }

    public function testIsCommentsEnabledRequiresPosts(): void
    {
        $context = $this->makeContext($this->allEnabled());
        self::assertTrue($context->isCommentsEnabled());

        $contextPostsOff = $this->makeContext(array_merge($this->allEnabled(), [
            ModuleParameterEnum::EditorialPostsEnabled->value => false,
        ]));
        self::assertFalse($contextPostsOff->isCommentsEnabled());
    }

    public function testIsFormsEnabled(): void
    {
        $context = $this->makeContext([
            ModuleParameterEnum::EditorialEnabled->value => true,
            ModuleParameterEnum::EditorialFormsEnabled->value => true,
        ]);
        self::assertTrue($context->isFormsEnabled());

        $contextAdminOff = $this->makeContext([
            ModuleParameterEnum::EditorialEnabled->value => false,
            ModuleParameterEnum::EditorialFormsEnabled->value => true,
        ]);
        self::assertFalse($contextAdminOff->isFormsEnabled());
    }

    public function testIsSitemapEnabledRequiresPosts(): void
    {
        $context = $this->makeContext($this->allEnabled());
        self::assertTrue($context->isSitemapEnabled());

        $contextPostsOff = $this->makeContext(array_merge($this->allEnabled(), [
            ModuleParameterEnum::EditorialPostsEnabled->value => false,
        ]));
        self::assertFalse($contextPostsOff->isSitemapEnabled());
    }
}
