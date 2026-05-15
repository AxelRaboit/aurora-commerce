<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSavedView;
use PHPUnit\Framework\TestCase;

final class ProjectSavedViewTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ProjectSavedView())->getId());
    }

    public function testFiltersDefaultsToEmptyArray(): void
    {
        self::assertSame([], (new ProjectSavedView())->getFilters());
    }

    public function testNameGetterAndSetter(): void
    {
        $view = (new ProjectSavedView())->setName('My View');

        self::assertSame('My View', $view->getName());
    }

    public function testOwnerGetterAndSetter(): void
    {
        $user = new User();
        $view = (new ProjectSavedView())->setOwner($user);

        self::assertSame($user, $view->getOwner());
    }

    public function testProjectGetterAndSetter(): void
    {
        $project = new Project();
        $view = (new ProjectSavedView())->setProject($project);

        self::assertSame($project, $view->getProject());
    }

    public function testFiltersGetterAndSetter(): void
    {
        $filters = ['status' => 'open', 'assignee' => 1];
        $view = (new ProjectSavedView())->setFilters($filters);

        self::assertSame($filters, $view->getFilters());
    }
}
