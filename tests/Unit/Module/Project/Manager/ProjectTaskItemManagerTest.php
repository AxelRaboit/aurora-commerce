<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Project\Dto\ProjectTaskItemsInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskItem;
use Aurora\Module\Project\Manager\ProjectTaskItemManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

#[AllowMockObjectsWithoutExpectations]
final class ProjectTaskItemManagerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ProjectTaskItemManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $this->manager = new ProjectTaskItemManager(
            $this->em,
            new AuditLogger($this->em, $security, new SequenceGenerator($this->createStub(Connection::class)), $this->createStub(SettingRepository::class)),
        );
    }

    private function makeTask(int $id = 1): ProjectTask
    {
        $project = new Project();
        (new ReflectionProperty(Project::class, 'id'))->setValue($project, 99);
        $task = new ProjectTask();
        $task->setProject($project);
        (new ReflectionProperty(ProjectTask::class, 'id'))->setValue($task, $id);

        return $task;
    }

    public function testReplaceForTaskRemovesExistingAndPersistsNew(): void
    {
        $task = $this->makeTask();
        $oldA = (new ProjectTaskItem())->setLabel('Old A')->setPosition(0);
        $oldB = (new ProjectTaskItem())->setLabel('Old B')->setPosition(1);
        $task->getItems()->add($oldA);
        $task->getItems()->add($oldB);

        $removed = [];
        $persisted = [];
        $this->em->method('remove')->willReturnCallback(function (object $entity) use (&$removed): void {
            $removed[] = $entity;
        });
        $this->em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $this->manager->replaceForTask($task, new ProjectTaskItemsInput([
            ['label' => 'New 1', 'done' => false],
            ['label' => 'New 2', 'done' => true],
        ]));

        // Old items removed.
        self::assertContains($oldA, $removed);
        self::assertContains($oldB, $removed);

        // 2 ProjectTaskItem persisted (audit log adds another but that's via AuditLogger directly).
        $newItems = array_values(array_filter($persisted, static fn (object $e): bool => $e instanceof ProjectTaskItem));
        self::assertCount(2, $newItems);
        self::assertSame('New 1', $newItems[0]->getLabel());
        self::assertSame(0, $newItems[0]->getPosition());
        self::assertFalse($newItems[0]->isDone());
        self::assertSame('New 2', $newItems[1]->getLabel());
        self::assertSame(1, $newItems[1]->getPosition());
        self::assertTrue($newItems[1]->isDone());
    }

    public function testReplaceForTaskAcceptsEmptyList(): void
    {
        $task = $this->makeTask();
        $this->em->expects(self::atLeastOnce())->method('flush');

        $this->manager->replaceForTask($task, new ProjectTaskItemsInput([]));

        self::assertCount(0, $task->getItems());
    }
}
