<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Manager;

use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectSavedView;
use Aurora\Module\Project\Manager\ProjectSavedViewManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ProjectSavedViewManagerTest extends TestCase
{
    public function testCreatePersistsAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $manager = new ProjectSavedViewManager($em);

        $owner = new User();
        $project = new Project();
        $view = $manager->create($owner, $project, 'My View', ['status' => 'open']);

        self::assertInstanceOf(ProjectSavedView::class, $view);
        self::assertSame($owner, $view->getOwner());
        self::assertSame($project, $view->getProject());
        self::assertSame('My View', $view->getName());
        self::assertSame(['status' => 'open'], $view->getFilters());
    }

    public function testUpdateModifiesAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $manager = new ProjectSavedViewManager($em);

        $view = new ProjectSavedView();
        $manager->update($view, 'New Name', ['priority' => 'high']);

        self::assertSame('New Name', $view->getName());
        self::assertSame(['priority' => 'high'], $view->getFilters());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $view = new ProjectSavedView();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('remove')->with($view);
        $em->expects(self::once())->method('flush');

        (new ProjectSavedViewManager($em))->delete($view);
    }
}
