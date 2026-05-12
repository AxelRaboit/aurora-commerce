<?php

declare(strict_types=1);

namespace Aurora\Module\Ged\DocumentTag\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Ged\DocumentTag\Dto\DocumentTagInputInterface;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTag;
use Aurora\Module\Ged\DocumentTag\Entity\DocumentTagInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DocumentTagManagerInterface::class)]
class DocumentTagManager implements DocumentTagManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(DocumentTagInputInterface $input): DocumentTagInterface
    {
        $tag = $this->createDocumentTag();
        $this->applyInput($tag, $input);
        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $this->auditCreated($tag);

        return $tag;
    }

    public function update(DocumentTagInterface $tag, DocumentTagInputInterface $input): void
    {
        $this->applyInput($tag, $input);
        $this->entityManager->flush();

        $this->auditUpdated($tag);
    }

    public function delete(DocumentTagInterface $tag): void
    {
        $this->auditDeleted($tag);

        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }

    protected function createDocumentTag(): DocumentTagInterface
    {
        return new DocumentTag();
    }

    protected function applyInput(DocumentTagInterface $tag, DocumentTagInputInterface $input): void
    {
        $tag->setName($input->getName());
        $tag->setColor($input->getColor());
    }

    protected function auditCreated(DocumentTagInterface $tag): void
    {
        $this->auditLogger->log('ged', 'tag.created', 'DocumentTag', $tag->getId(), $this->auditPayload($tag));
    }

    protected function auditUpdated(DocumentTagInterface $tag): void
    {
        $this->auditLogger->log('ged', 'tag.updated', 'DocumentTag', $tag->getId(), $this->auditPayload($tag));
    }

    protected function auditDeleted(DocumentTagInterface $tag): void
    {
        $this->auditLogger->log('ged', 'tag.deleted', 'DocumentTag', $tag->getId(), $this->auditPayload($tag));
    }

    protected function auditPayload(DocumentTagInterface $tag): array
    {
        return ['name' => $tag->getName(), 'color' => $tag->getColor()];
    }
}
