<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\PasswordGenerator;

use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Module\PasswordGenerator\PasswordGeneratorModule;
use PHPUnit\Framework\TestCase;

final class PasswordGeneratorModuleTest extends TestCase
{
    public function testGetIdReturnsModuleSlug(): void
    {
        self::assertSame('password_generator', (new PasswordGeneratorModule())->getId());
    }

    public function testGetPermissionsContainsUsePermission(): void
    {
        $permissions = (new PasswordGeneratorModule())->getPermissions();

        self::assertCount(1, $permissions);
        self::assertContainsOnlyInstancesOf(NavPermission::class, $permissions);
    }

    public function testGetNavSectionsIsEmpty(): void
    {
        self::assertSame([], (new PasswordGeneratorModule())->getNavSections());
    }

    public function testGetCatalogNavSectionsIsEmpty(): void
    {
        self::assertSame([], (new PasswordGeneratorModule())->getCatalogNavSections());
    }
}
