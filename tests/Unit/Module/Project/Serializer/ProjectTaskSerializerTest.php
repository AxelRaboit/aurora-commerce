<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Serializer;

use Aurora\Core\Media\Entity\Media;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Entity\ProjectSprint;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskComment;
use Aurora\Module\Project\Entity\ProjectTaskItem;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use Aurora\Module\Project\Enum\ProjectTaskPriorityEnum;
use Aurora\Module\Project\Serializer\ProjectTaskCommentSerializer;
use Aurora\Module\Project\Serializer\ProjectTaskSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProjectTaskSerializerTest extends TestCase
{
    private ProjectTaskSerializer $serializer;

    protected function setUp(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $this->serializer = new ProjectTaskSerializer($translator, new ProjectTaskCommentSerializer());
    }

    private function makeTask(): ProjectTask
    {
        $project = new Project();
        (new ReflectionProperty(Project::class, 'id'))->setValue($project, 1);

        $column = new ProjectColumn();
        $column->setProject($project)->setLabel('À faire')->setPosition(0);
        (new ReflectionProperty(ProjectColumn::class, 'id'))->setValue($column, 10);

        $task = new ProjectTask();
        $task->setProject($project)
            ->setColumn($column)
            ->setTitle('Demo')
            ->setReference('TSK-000001')
            ->setPriority(ProjectTaskPriorityEnum::High)
            ->setPosition(2)
            ->setStoryPoints(5)
            ->setEstimateMinutes(120)
            ->setDueDate(new DateTimeImmutable('2026-05-01'));
        (new ReflectionProperty(ProjectTask::class, 'id'))->setValue($task, 42);
        (new ReflectionProperty(ProjectTask::class, 'createdAt'))->setValue($task, new DateTimeImmutable('2026-01-01'));
        (new ReflectionProperty(ProjectTask::class, 'updatedAt'))->setValue($task, new DateTimeImmutable('2026-01-02'));

        return $task;
    }

    public function testSerializeBasicScalarFields(): void
    {
        $task = $this->makeTask();
        $payload = $this->serializer->serialize($task);

        self::assertSame(42, $payload['id']);
        self::assertSame('TSK-000001', $payload['reference']);
        self::assertSame('Demo', $payload['title']);
        self::assertSame(10, $payload['columnId']);
        self::assertSame('high', $payload['priority']);
        self::assertSame(5, $payload['storyPoints']);
        self::assertSame(120, $payload['estimateMinutes']);
        self::assertSame('2026-05-01', $payload['dueDate']);
        self::assertSame(2, $payload['position']);
    }

    public function testSerializeAggregatesItemsAndChecklistCounts(): void
    {
        $task = $this->makeTask();
        $itemA = (new ProjectTaskItem())->setLabel('A')->setDone(true)->setPosition(0);
        $itemB = (new ProjectTaskItem())->setLabel('B')->setDone(false)->setPosition(1);
        $itemC = (new ProjectTaskItem())->setLabel('C')->setDone(true)->setPosition(2);
        $task->getItems()->add($itemA);
        $task->getItems()->add($itemB);
        $task->getItems()->add($itemC);

        $payload = $this->serializer->serialize($task);

        self::assertSame(3, $payload['itemsTotal']);
        self::assertSame(2, $payload['itemsDone']);
        self::assertCount(3, $payload['items']);
        self::assertSame('A', $payload['items'][0]['label']);
        self::assertTrue($payload['items'][0]['done']);
    }

    public function testSerializeSumsLoggedMinutes(): void
    {
        $task = $this->makeTask();
        $user = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($user, 1);

        $entry1 = (new ProjectTaskTimeEntry())->setMinutes(30)->setUser($user)->setLoggedAt(new DateTimeImmutable());
        $entry2 = (new ProjectTaskTimeEntry())->setMinutes(45)->setUser($user)->setLoggedAt(new DateTimeImmutable());
        $task->getTimeEntries()->add($entry1);
        $task->getTimeEntries()->add($entry2);

        $payload = $this->serializer->serialize($task);

        self::assertSame(75, $payload['loggedMinutes']);
    }

    public function testSerializeIncludesLabelsWatchersAttachmentsSprint(): void
    {
        $task = $this->makeTask();

        $label = new ProjectLabel();
        $label->setProject($task->getProject())->setName('Bug')->setColor('rose');
        (new ReflectionProperty(ProjectLabel::class, 'id'))->setValue($label, 11);
        $task->addLabel($label);

        $watcher = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($watcher, 22);
        $task->addWatcher($watcher);

        $media = new Media();
        $media->setOriginalName('plan.pdf')->setPath('plan.pdf')->setMimeType('application/pdf');
        (new ReflectionProperty(Media::class, 'id'))->setValue($media, 33);
        $task->addAttachment($media);

        $sprint = new ProjectSprint();
        $sprint->setProject($task->getProject())->setName('Sprint 1');
        (new ReflectionProperty(ProjectSprint::class, 'id'))->setValue($sprint, 44);
        $task->setSprint($sprint);

        $payload = $this->serializer->serialize($task);

        self::assertSame([11], $payload['labelIds']);
        self::assertSame([22], $payload['watcherIds']);
        self::assertSame(44, $payload['sprintId']);
        self::assertCount(1, $payload['attachments']);
        self::assertSame('plan.pdf', $payload['attachments'][0]['name']);
        self::assertSame(33, $payload['attachments'][0]['id']);
    }

    public function testSerializeIncludesCommentsAndCount(): void
    {
        $task = $this->makeTask();
        $author = new User();
        $author->setName('Alice');
        (new ReflectionProperty(User::class, 'id'))->setValue($author, 1);
        $comment = new ProjectTaskComment();
        $comment->setTask($task)->setAuthor($author)->setContent('Hello');
        (new ReflectionProperty(ProjectTaskComment::class, 'id'))->setValue($comment, 100);
        (new ReflectionProperty(ProjectTaskComment::class, 'createdAt'))->setValue($comment, new DateTimeImmutable());
        $task->getComments()->add($comment);

        $payload = $this->serializer->serialize($task);

        self::assertSame(1, $payload['commentCount']);
        self::assertCount(1, $payload['comments']);
        self::assertSame('Hello', $payload['comments'][0]['content']);
        self::assertSame('Alice', $payload['comments'][0]['authorName']);
    }
}
