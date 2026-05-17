<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Media\Library\Entity\MediaInterface;
use Aurora\Core\Media\Library\Repository\MediaRepository;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectTaskAttachmentManagerInterface::class)]
class ProjectTaskAttachmentManager implements ProjectTaskAttachmentManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MediaRepository $mediaRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    /** @param list<int> $mediaIds */
    public function attach(ProjectTaskInterface $task, array $mediaIds): int
    {
        if ([] === $mediaIds) {
            return 0;
        }

        $existingIds = [];
        foreach ($task->getAttachments() as $existing) {
            $existingIds[(int) $existing->getId()] = true;
        }

        $added = 0;
        foreach ($this->mediaRepository->findBy(['id' => $mediaIds]) as $media) {
            if (isset($existingIds[(int) $media->getId()])) {
                continue;
            }

            $task->addAttachment($media);
            ++$added;
        }

        if ($added > 0) {
            $this->entityManager->flush();
            $this->auditLogger->log('project', 'task.attachment.added', 'ProjectTask', $task->getId(), [
                'projectId' => $task->getProject()->getId(),
                'count' => $added,
            ]);
        }

        return $added;
    }

    public function detach(ProjectTaskInterface $task, MediaInterface $media): void
    {
        if (!$task->getAttachments()->contains($media)) {
            return;
        }

        $task->removeAttachment($media);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.attachment.removed', 'ProjectTask', $task->getId(), [
            'projectId' => $task->getProject()->getId(),
            'mediaId' => $media->getId(),
        ]);
    }
}
