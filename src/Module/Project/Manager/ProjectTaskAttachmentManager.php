<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Entity\Media;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Module\Project\Entity\ProjectTask;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectTaskAttachmentManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MediaRepository $mediaRepository,
        private AuditLogger $auditLogger,
    ) {}

    /** @param list<int> $mediaIds */
    public function attach(ProjectTask $task, array $mediaIds): int
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

    public function detach(ProjectTask $task, Media $media): void
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
