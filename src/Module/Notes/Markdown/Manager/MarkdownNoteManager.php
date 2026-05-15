<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Markdown\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Module\Notes\Markdown\Dto\MarkdownNoteInputInterface;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\Notes\Markdown\Repository\MarkdownNoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MarkdownNoteManagerInterface::class)]
class MarkdownNoteManager implements MarkdownNoteManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MarkdownNoteRepository $noteRepository,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(CoreUserInterface $user, MarkdownNoteInputInterface $input): MarkdownNoteInterface
    {
        $note = $this->createNote();
        $note->setUser($user);
        $note->setAgency($user->getAgency());

        $this->applyInput($note, $input);

        if (null === $input->getPosition()) {
            $maxPosition = $this->noteRepository->findMaxPositionForUserAndParent($user, $input->getParentId());
            $note->setPosition(null === $maxPosition ? 0 : $maxPosition + 1);
        }

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        $this->auditCreated($note);

        return $note;
    }

    public function update(MarkdownNoteInterface $note, MarkdownNoteInputInterface $input): void
    {
        $this->applyInput($note, $input);
        $this->entityManager->flush();

        $this->auditUpdated($note);
    }

    public function delete(MarkdownNoteInterface $note): void
    {
        $this->auditDeleted($note);

        $this->entityManager->remove($note);
        $this->entityManager->flush();
    }

    public function move(MarkdownNoteInterface $note, ?MarkdownNoteInterface $parent): void
    {
        $note->setParent($parent);
        $this->entityManager->flush();
    }

    public function reorder(CoreUserInterface $user, array $orderedIds): void
    {
        if ([] === $orderedIds) {
            return;
        }

        $notes = $this->noteRepository->findBy(['id' => $orderedIds, 'user' => $user]);
        $byId = [];
        foreach ($notes as $note) {
            $byId[$note->getId()] = $note;
        }

        foreach ($orderedIds as $position => $id) {
            $note = $byId[$id] ?? null;
            if (null === $note) {
                continue;
            }
            $note->setPosition($position);
        }

        $this->entityManager->flush();
    }

    /**
     * Hook: instantiate the concrete note class. Clients override to return
     * their own substituted entity.
     */
    protected function createNote(): MarkdownNoteInterface
    {
        return new MarkdownNote();
    }

    /**
     * Hook: hydrate the note from the DTO. Clients override to add custom
     * fields, e.g. parent::applyInput($note, $input); $note->setExtra(...).
     */
    protected function applyInput(MarkdownNoteInterface $note, MarkdownNoteInputInterface $input): void
    {
        $note->setTitle($input->getTitle());
        $note->setContent($input->getContent());
        $note->setTags($input->getTags());

        if (null !== $input->getPosition()) {
            $note->setPosition($input->getPosition());
        }

        if (null !== $input->getParentId()) {
            $parent = $this->noteRepository->findOneByUserAndId($note->getUser(), $input->getParentId());
            $note->setParent($parent);
        } else {
            $note->setParent(null);
        }
    }

    protected function auditCreated(MarkdownNoteInterface $note): void
    {
        $this->auditLogger->log('notes_markdown', 'note.created', 'MarkdownNote', $note->getId(), $this->auditPayload($note));
    }

    protected function auditUpdated(MarkdownNoteInterface $note): void
    {
        $this->auditLogger->log('notes_markdown', 'note.updated', 'MarkdownNote', $note->getId(), $this->auditPayload($note));
    }

    protected function auditDeleted(MarkdownNoteInterface $note): void
    {
        $this->auditLogger->log('notes_markdown', 'note.deleted', 'MarkdownNote', $note->getId(), $this->auditPayload($note));
    }

    /**
     * Hook: build the audit payload. Clients override to splat-merge their
     * own custom fields, e.g.
     *   return [...parent::auditPayload($note), 'custom' => $note->getCustom()];
     *
     * @return array<string, mixed>
     */
    protected function auditPayload(MarkdownNoteInterface $note): array
    {
        return [
            'title' => $note->getTitle(),
            'tags' => $note->getTags(),
            'parentId' => $note->getParent()?->getId(),
            'position' => $note->getPosition(),
        ];
    }
}
