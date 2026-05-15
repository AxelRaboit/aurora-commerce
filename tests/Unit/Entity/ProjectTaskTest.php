<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Entity\ProjectSprint;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ProjectTaskTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new ProjectTask())->getId());
    }

    public function testCollectionsInitialized(): void
    {
        $task = new ProjectTask();

        self::assertCount(0, $task->getLabels());
        self::assertCount(0, $task->getItems());
        self::assertCount(0, $task->getTimeEntries());
        self::assertCount(0, $task->getComments());
        self::assertCount(0, $task->getAttachments());
        self::assertCount(0, $task->getWatchers());
    }

    public function testDefaultValues(): void
    {
        $task = new ProjectTask();

        self::assertNull($task->getReference());
        self::assertNull($task->getDescription());
        self::assertSame(ProjectTaskPriorityEnum::Medium, $task->getPriority());
        self::assertNull($task->getAssignee());
        self::assertNull($task->getDueDate());
        self::assertSame(0, $task->getPosition());
        self::assertNull($task->getStoryPoints());
        self::assertNull($task->getEstimateMinutes());
        self::assertNull($task->getSprint());
    }

    public function testTitleAndDescriptionGettersAndSetters(): void
    {
        $task = (new ProjectTask())->setTitle('Build feature')->setDescription('A feature');

        self::assertSame('Build feature', $task->getTitle());
        self::assertSame('A feature', $task->getDescription());
    }

    public function testProjectAndColumnGettersAndSetters(): void
    {
        $project = new Project();
        $column = new ProjectColumn();
        $task = (new ProjectTask())->setProject($project)->setColumn($column);

        self::assertSame($project, $task->getProject());
        self::assertSame($column, $task->getColumn());
    }

    public function testPriorityGetterAndSetter(): void
    {
        $task = (new ProjectTask())->setPriority(ProjectTaskPriorityEnum::Urgent);

        self::assertSame(ProjectTaskPriorityEnum::Urgent, $task->getPriority());
    }

    public function testAssigneeGetterAndSetter(): void
    {
        $user = new User();
        $task = (new ProjectTask())->setAssignee($user);

        self::assertSame($user, $task->getAssignee());

        $task->setAssignee(null);
        self::assertNull($task->getAssignee());
    }

    public function testDueDateGetterAndSetter(): void
    {
        $date = new DateTimeImmutable('2026-12-31');
        $task = (new ProjectTask())->setDueDate($date);

        self::assertSame($date, $task->getDueDate());
    }

    public function testPositionStoryPointsAndEstimate(): void
    {
        $task = (new ProjectTask())->setPosition(5)->setStoryPoints(3)->setEstimateMinutes(120);

        self::assertSame(5, $task->getPosition());
        self::assertSame(3, $task->getStoryPoints());
        self::assertSame(120, $task->getEstimateMinutes());
    }

    public function testSprintGetterAndSetter(): void
    {
        $sprint = new ProjectSprint();
        $task = (new ProjectTask())->setSprint($sprint);

        self::assertSame($sprint, $task->getSprint());

        $task->setSprint(null);
        self::assertNull($task->getSprint());
    }

    public function testAddAndRemoveLabel(): void
    {
        $task = new ProjectTask();
        $label = new ProjectLabel();

        $task->addLabel($label);
        self::assertCount(1, $task->getLabels());

        $task->addLabel($label);
        self::assertCount(1, $task->getLabels(), 'duplicate ignored');

        $task->removeLabel($label);
        self::assertCount(0, $task->getLabels());
    }

    public function testAddAndRemoveAttachment(): void
    {
        $task = new ProjectTask();
        $media = $this->createStub(MediaInterface::class);

        $task->addAttachment($media);
        self::assertCount(1, $task->getAttachments());

        $task->addAttachment($media);
        self::assertCount(1, $task->getAttachments(), 'duplicate ignored');

        $task->removeAttachment($media);
        self::assertCount(0, $task->getAttachments());
    }

    public function testAddAndRemoveWatcher(): void
    {
        $task = new ProjectTask();
        $user = $this->createStub(CoreUserInterface::class);

        $task->addWatcher($user);
        self::assertCount(1, $task->getWatchers());

        $task->addWatcher($user);
        self::assertCount(1, $task->getWatchers(), 'duplicate ignored');

        $task->removeWatcher($user);
        self::assertCount(0, $task->getWatchers());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $task = (new ProjectTask())->setReference('TASK-001');

        self::assertSame('TASK-001', $task->getReference());
    }
}
