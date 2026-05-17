<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Module\Project;

use Aurora\Core\Notification\Repository\NotificationRepository;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Project\Dto\ProjectInput;
use Aurora\Module\Project\Dto\ProjectTaskInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use Aurora\Module\Project\Manager\ProjectManager;
use Aurora\Module\Project\Manager\ProjectTaskManager;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class ProjectTaskManagerTest extends IntegrationTestCase
{
    private ProjectManager $projectManager;
    private ProjectTaskManager $taskManager;
    private EntityManagerInterface $entityManager;
    private NotificationRepository $notificationRepository;

    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->projectManager = static::getContainer()->get(ProjectManager::class);
        $this->taskManager = static::getContainer()->get(ProjectTaskManager::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->notificationRepository = static::getContainer()->get(NotificationRepository::class);
    }

    private function makeProject(): Project
    {
        return $this->projectManager->create(new ProjectInput(title: 'Project', status: ProjectStatusEnum::Active->value));
    }

    private function findUser(): User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        self::assertNotNull($user, 'AppFixtures should provide at least one user');

        return $user;
    }

    public function testCreateAssignsTaskWithReferenceAndColumn(): void
    {
        $project = $this->makeProject();
        $column = $project->getColumns()->first();

        $task = $this->taskManager->create(
            $project,
            new ProjectTaskInput(
                title: 'A task',
                columnId: $column->getId(),
                priority: ProjectTaskPriorityEnum::High->value,
            ),
        );

        self::assertNotNull($task->getId());
        self::assertStringStartsWith('TSK-', (string) $task->getReference());
        self::assertSame($column->getId(), $task->getColumn()->getId());
        self::assertSame(ProjectTaskPriorityEnum::High, $task->getPriority());
    }

    public function testCreateNotifiesAssignee(): void
    {
        $project = $this->makeProject();
        $column = $project->getColumns()->first();
        $assignee = $this->findUser();

        $this->taskManager->create($project, new ProjectTaskInput(
            title: 'Assigned task',
            columnId: $column->getId(),
            assigneeId: $assignee->getId(),
        ));

        $notifications = $this->notificationRepository->findRecentForUser($assignee);
        $types = array_map(static fn ($n): string => $n->getType(), $notifications);
        self::assertContains('project.task.assigned', $types);
    }

    public function testUpdateSyncsLabelsAtomically(): void
    {
        $project = $this->makeProject();
        $column = $project->getColumns()->first();

        // Seed two labels.
        $label1 = (new ProjectLabel())->setProject($project)->setName('Bug')->setColor('rose');
        $label2 = (new ProjectLabel())->setProject($project)->setName('Feature')->setColor('emerald');
        $this->entityManager->persist($label1);
        $this->entityManager->persist($label2);
        $this->entityManager->flush();

        $task = $this->taskManager->create($project, new ProjectTaskInput(
            title: 'Labelled',
            columnId: $column->getId(),
            labelIds: [$label1->getId(), $label2->getId()],
        ));
        self::assertCount(2, $task->getLabels());

        // Now drop label2 — sync should remove it.
        $this->taskManager->update($task, new ProjectTaskInput(
            title: 'Labelled',
            columnId: $column->getId(),
            priority: ProjectTaskPriorityEnum::Medium->value,
            labelIds: [$label1->getId()],
        ));

        $this->entityManager->refresh($task);
        $names = array_map(static fn ($l) => $l->getName(), $task->getLabels()->toArray());
        self::assertSame(['Bug'], $names);
    }
}
