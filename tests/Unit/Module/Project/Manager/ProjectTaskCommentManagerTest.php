<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Project\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Notification\Entity\Notification;
use Aurora\Core\Notification\Manager\NotificationManager;
use Aurora\Core\Notification\Repository\NotificationRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Dto\ProjectTaskCommentInput;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Manager\ProjectTaskCommentManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class ProjectTaskCommentManagerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ProjectTaskCommentManager $manager;
    /** @var list<Notification> */
    private array $persistedNotifications = [];

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $auditLogger = new AuditLogger($this->em, $security, new SequenceGenerator($this->createStub(Connection::class)), $this->createStub(SettingRepository::class));

        $this->persistedNotifications = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity): void {
            if ($entity instanceof Notification) {
                $this->persistedNotifications[] = $entity;
            }
        });

        $notifier = new NotificationManager($this->em, $this->createStub(NotificationRepository::class));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $params = [], ?string $domain = null, ?string $locale = null): string => str_replace(array_keys($params), array_values($params), '%name% commented: %content%')
        );

        $this->manager = new ProjectTaskCommentManager($this->em, $auditLogger, $notifier, $translator);
    }

    private function makeUser(int $id, string $name = 'User'): User
    {
        $user = new User();
        $user->setName($name);
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function makeTask(?User $assignee = null, array $watchers = []): ProjectTask
    {
        $project = new Project();
        $project->setTitle('Demo');
        (new ReflectionProperty(Project::class, 'id'))->setValue($project, 1);
        $task = new ProjectTask();
        $task->setProject($project)->setTitle('Task X');
        (new ReflectionProperty(ProjectTask::class, 'id'))->setValue($task, 42);
        if (null !== $assignee) {
            $task->setAssignee($assignee);
        }
        foreach ($watchers as $watcher) {
            $task->addWatcher($watcher);
        }

        return $task;
    }

    public function testCreateNotifiesAssigneeAndWatchersExceptAuthor(): void
    {
        $author = $this->makeUser(1, 'Alice');
        $assignee = $this->makeUser(2, 'Bob');
        $watcher1 = $this->makeUser(3, 'Carol');
        $watcher2 = $this->makeUser(1, 'Alice'); // same id as author → should be excluded
        $task = $this->makeTask($assignee, [$watcher1, $watcher2]);

        $this->manager->create($task, $author, new ProjectTaskCommentInput(content: 'Hello'));

        $recipientIds = array_map(static fn (Notification $n): ?int => $n->getRecipient()->getId(), $this->persistedNotifications);

        self::assertContains(2, $recipientIds);
        self::assertContains(3, $recipientIds);
        self::assertNotContains(1, $recipientIds, 'author should never be notified');
    }

    public function testCreateSkipsAssigneeWhenSameAsAuthor(): void
    {
        $self = $this->makeUser(7, 'Self');
        $task = $this->makeTask($self);

        $this->manager->create($task, $self, new ProjectTaskCommentInput(content: 'My note'));

        $recipientIds = array_map(static fn (Notification $n): ?int => $n->getRecipient()->getId(), $this->persistedNotifications);
        self::assertNotContains(7, $recipientIds);
    }

    public function testCreateBuildsBodyWithAuthorNameAndPreview(): void
    {
        $author = $this->makeUser(1, 'Alice');
        $assignee = $this->makeUser(2, 'Bob');
        $task = $this->makeTask($assignee);

        $this->manager->create($task, $author, new ProjectTaskCommentInput(content: 'A long enough comment to verify the preview formatting.'));

        self::assertCount(1, $this->persistedNotifications);
        $notification = $this->persistedNotifications[0];
        self::assertSame('project.task.commented', $notification->getType());
        self::assertSame('Task X', $notification->getTitle());
        self::assertStringContainsString('Alice commented', (string) $notification->getBody());
        self::assertStringContainsString('long enough comment', (string) $notification->getBody());
    }

    public function testCreateNoNotificationWhenNoAssigneeAndNoWatchers(): void
    {
        $author = $this->makeUser(1, 'Alice');
        $task = $this->makeTask(); // no assignee, no watchers

        $this->manager->create($task, $author, new ProjectTaskCommentInput(content: 'Solo'));

        self::assertCount(0, $this->persistedNotifications);
    }
}
