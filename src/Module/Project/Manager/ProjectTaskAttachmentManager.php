<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Repository\DocumentRepository;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectTaskAttachmentManagerInterface::class)]
class ProjectTaskAttachmentManager implements ProjectTaskAttachmentManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly DocumentRepository $documentRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    /** @param list<int> $documentIds */
    public function attach(ProjectTaskInterface $task, array $documentIds): int
    {
        if ([] === $documentIds) {
            return 0;
        }

        $existingIds = [];
        foreach ($task->getAttachments() as $existing) {
            $existingIds[(int) $existing->getId()] = true;
        }

        $added = 0;
        foreach ($this->documentRepository->findBy(['id' => $documentIds]) as $document) {
            if (isset($existingIds[(int) $document->getId()])) {
                continue;
            }

            $task->addAttachment($document);
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

    public function detach(ProjectTaskInterface $task, DocumentInterface $document): void
    {
        if (!$task->getAttachments()->contains($document)) {
            return;
        }

        $task->removeAttachment($document);
        $this->entityManager->flush();

        $this->auditLogger->log('project', 'task.attachment.removed', 'ProjectTask', $task->getId(), [
            'projectId' => $task->getProject()->getId(),
            'documentId' => $document->getId(),
        ]);
    }
}
