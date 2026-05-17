<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Service;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Core\Configuration\Setting\Enum\ModuleParameterEnum;
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
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialBackend->value => true])->isBackendEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialBackend->value => false])->isBackendEnabled());
    }

    public function testIsPostsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialPosts->value => true])->isPostsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialPosts->value => false])->isPostsEnabled());
    }

    public function testIsMenusEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialMenus->value => true])->isMenusEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialMenus->value => false])->isMenusEnabled());
    }

    public function testIsPostTypesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialPostTypes->value => true])->isPostTypesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialPostTypes->value => false])->isPostTypesEnabled());
    }

    public function testIsTaxonomiesEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialTaxonomies->value => true])->isTaxonomiesEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialTaxonomies->value => false])->isTaxonomiesEnabled());
    }

    public function testIsCommentsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialComments->value => true])->isCommentsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialComments->value => false])->isCommentsEnabled());
    }

    public function testIsFormsEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialForms->value => true])->isFormsEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialForms->value => false])->isFormsEnabled());
    }

    public function testIsSitemapEnabled(): void
    {
        self::assertTrue($this->makeContext([ModuleParameterEnum::EditorialSitemap->value => true])->isSitemapEnabled());
        self::assertFalse($this->makeContext([ModuleParameterEnum::EditorialSitemap->value => false])->isSitemapEnabled());
    }
}
