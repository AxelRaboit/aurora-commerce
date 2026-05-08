<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Notification\Manager\NotificationManager;
use Aurora\Core\User\Entity\User;
use Aurora\Module\Project\Dto\ProjectTaskCommentInput;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskComment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProjectTaskCommentManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
        private NotificationManager $notifier,
        private TranslatorInterface $translator,
    ) {}

    public function create(ProjectTask $task, User $author, ProjectTaskCommentInput $input): ProjectTaskComment
    {
        $comment = new ProjectTaskComment();
        $comment->setTask($task)->setAuthor($author)->setContent($input->content);
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
                    '%content%' => mb_substr($input->content, 0, 200),
                ], null, $recipient->getLocale()->value),
                null,
                ['projectId' => $task->getProject()->getId(), 'taskId' => $task->getId(), 'commentId' => $comment->getId()],
            );
        }

        return $comment;
    }

    public function delete(ProjectTaskComment $comment): void
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
}
