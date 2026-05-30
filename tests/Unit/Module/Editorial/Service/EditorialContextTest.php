<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Editorial\EditorialContext;
use Aurora\Module\Editorial\Setting\EditorialModuleParameterEnum;
use PHPUnit\Framework\TestCase;

final class EditorialContextTest extends TestCase
{
    /** @param array<string, bool> $values */
    private function makeContext(array $values): EditorialContext
    {
        $checker = $this->createStub(ModuleAccessChecker::class);
        $checker->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => $values[$module] ?? true,
        );

        return new EditorialContext($checker);
    }

    public function testIsAdminEnabled(): void
    {
        self::assertTrue($this->makeContext([EditorialModuleParameterEnum::Backend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([EditorialModuleParameterEnum::Backend->value => false])->isBackendEnabled());
    }

    public function testIsPostsEnabled(): void
    {
        self::assertTrue($this->makeContext([EditorialModuleParameterEnum::Posts->value => true])->isPostsEnabled());
        self::assertFalse($this->makeContext([EditorialModuleParameterEnum::Posts->value => false])->isPostsEnabled());
    }

    public function testIsMenusEnabled(): void
    {
        self::assertTrue($this->makeContext([EditorialModuleParameterEnum::Menus->value => true])->isMenusEnabled());
        self::assertFalse($this->makeContext([EditorialModuleParameterEnum::Menus->value => false])->isMenusEnabled());
    }

    public function testIsPostTypesEnabled(): void
    {
        self::assertTrue($this->makeContext([EditorialModuleParameterEnum::PostTypes->value => true])->isPostTypesEnabled());
        self::assertFalse($this->makeContext([EditorialModuleParameterEnum::PostTypes->value => false])->isPostTypesEnabled());
    }

    public function testIsTaxonomiesEnabled(): void
    {
        self::assertTrue($this->makeContext([EditorialModuleParameterEnum::Taxonomies->value => true])->isTaxonomiesEnabled());
        self::assertFalse($this->makeContext([EditorialModuleParameterEnum::Taxonomies->value => false])->isTaxonomiesEnabled());
    }

    public function testIsCommentsEnabled(): void
    {
        self::assertTrue($this->makeContext([EditorialModuleParameterEnum::Comments->value => true])->isCommentsEnabled());
        self::assertFalse($this->makeContext([EditorialModuleParameterEnum::Comments->value => false])->isCommentsEnabled());
    }

    public function testIsFormsEnabled(): void
    {
        self::assertTrue($this->makeContext([EditorialModuleParameterEnum::Forms->value => true])->isFormsEnabled());
        self::assertFalse($this->makeContext([EditorialModuleParameterEnum::Forms->value => false])->isFormsEnabled());
    }

    public function testIsSitemapEnabled(): void
    {
        self::assertTrue($this->makeContext([EditorialModuleParameterEnum::Sitemap->value => true])->isSitemapEnabled());
        self::assertFalse($this->makeContext([EditorialModuleParameterEnum::Sitemap->value => false])->isSitemapEnabled());
    }
}
