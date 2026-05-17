<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Notification\Manager\NotificationManager;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Module\Project\Dto\ProjectTaskCommentInputInterface;
use Aurora\Module\Project\Entity\ProjectTaskComment;
use Aurora\Module\Project\Entity\ProjectTaskCommentInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ProjectTaskCommentManagerInterface::class)]
class ProjectTaskCommentManager implements ProjectTaskCommentManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly NotificationManager $notifier,
        protected readonly TranslatorInterface $translator,
    ) {}

    public function create(ProjectTaskInterface $task, User $author, ProjectTaskCommentInputInterface $input): ProjectTaskCommentInterface
    {
        $comment = $this->createProjectTaskComment();
        $comment->setTask($task)->setAuthor($author)->setContent($input->getContent());
        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.comment.created', 'ProjectTask', $task->getId(), [
            'projectId' => $task->getProject()->getId(),
            'commentId' => $comment->getId(),
        ]);

        // Notify the assignee + watchers (excluding the author).
        $recipients = [];
        if ($task->getAssignee() instanceof User && $task->getAssignee()->getId() !== $author->getId()) {
            $recipients[(int) $task->getAssignee()->getId()] = $task->getAssignee();
        }

        foreach ($task->getWatchers() as $watcher) {
            if ($watcher->getId() !== $author->getId()) {
                $recipients[(int) $watcher->getId()] = $watcher;
            }
        }

        foreach ($recipients as $recipient) {
            $this->notifier->notify(
                $recipient,
                'project.task.commented',
                $task->getTitle(),
                $this->translator->trans('backend.notifications.taskCommented', [
                    '%name%' => $author->getName(),
                    '%content%' => mb_substr($input->getContent(), 0, 200),
                ], null, $recipient->getLocale()->value),
                null,
                ['projectId' => $task->getProject()->getId(), 'taskId' => $task->getId(), 'commentId' => $comment->getId()],
            );
        }

        return $comment;
    }

    public function delete(ProjectTaskCommentInterface $comment): void
    {
        $taskId = $comment->getTask()->getId();
        $projectId = $comment->getTask()->getProject()->getId();
        $commentId = $comment->getId();

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.comment.deleted', 'ProjectTask', $taskId, [
            'projectId' => $projectId,
            'commentId' => $commentId,
        ]);
    }

    protected function createProjectTaskComment(): ProjectTaskCommentInterface
    {
        return new ProjectTaskComment();
    }
}
