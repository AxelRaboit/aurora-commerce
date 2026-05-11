<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Setting\Enum\ModuleParameterEnum;
use Aurora\Module\Editorial\Service\EditorialContext;
use PHPUnit\Framework\TestCase;

final class EditorialContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): EditorialContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (ModuleParameterEnum $module): bool => $values[$module->value] ?? true,
        );

        return new EditorialContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialEnabled->value => true])->isAdminEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialEnabled->value => false])->isAdminEnabled());
    }

    public function testIsPostsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialPostsEnabled->value => true])->isPostsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialPostsEnabled->value => false])->isPostsEnabled());
    }

    public function testIsMenusEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialMenusEnabled->value => true])->isMenusEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialMenusEnabled->value => false])->isMenusEnabled());
    }

    public function testIsPostTypesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialPostTypesEnabled->value => true])->isPostTypesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialPostTypesEnabled->value => false])->isPostTypesEnabled());
    }

    public function testIsTaxonomiesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialTaxonomiesEnabled->value => true])->isTaxonomiesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialTaxonomiesEnabled->value => false])->isTaxonomiesEnabled());
    }

    public function testIsCommentsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialCommentsEnabled->value => true])->isCommentsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialCommentsEnabled->value => false])->isCommentsEnabled());
    }

    public function testIsFormsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialFormsEnabled->value => true])->isFormsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialFormsEnabled->value => false])->isFormsEnabled());
    }

    public function testIsSitemapEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialSitemapEnabled->value => true])->isSitemapEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialSitemapEnabled->value => false])->isSitemapEnabled());
    }
}
