<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\PostIt\Manager;

use Aurora\Module\Dev\Audit\Service\AuditLogger;
use Aurora\Module\Notes\PostIt\Dto\PostItNoteInputInterface;
use Aurora\Module\Notes\PostIt\Entity\PostItNote;
use Aurora\Module\Notes\PostIt\Entity\PostItNoteInterface;
use Aurora\Module\Platform\User\Entity\CoreUserInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PostItNoteManagerInterface::class)]
class PostItNoteManager implements PostItNoteManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
    ) {}

    public function create(CoreUserInterface $user, PostItNoteInputInterface $input): PostItNoteInterface
    {
        $note = $this->createNote();
        $note->setUser($user);
        $note->setAgency($user->getAgency());

        $this->applyInput($note, $input);

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        $this->auditCreated($note);

        return $note;
    }

    public function update(PostItNoteInterface $note, PostItNoteInputInterface $input): void
    {
        $this->applyInput($note, $input);
        $this->entityManager->flush();
        $this->auditUpdated($note);
    }

    public function delete(PostItNoteInterface $note): void
    {
        $this->auditDeleted($note);
        $this->entityManager->remove($note);
        $this->entityManager->flush();
    }

    public function move(PostItNoteInterface $note, int $positionX, int $positionY): void
    {
        $note->setPositionX($positionX);
        $note->setPositionY($positionY);

        $this->entityManager->flush();
    }

    protected function createNote(): PostItNoteInterface
    {
        return new PostItNote();
    }

    protected function applyInput(PostItNoteInterface $note, PostItNoteInputInterface $input): void
    {
        $note->setTitle($input->getTitle());
        $note->setContent($input->getContent());
        if (null !== $input->getColor()) {
            $note->setColor($input->getColor());
        }

        if (null !== $input->getPositionX()) {
            $note->setPositionX($input->getPositionX());
        }

        if (null !== $input->getPositionY()) {
            $note->setPositionY($input->getPositionY());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditPayload(PostItNoteInterface $note): array
    {
        return [
            'id' => $note->getId(),
            'userId' => $note->getUser()->getId(),
            'color' => $note->getColor(),
        ];
    }

    protected function auditCreated(PostItNoteInterface $note): void
    {
        $this->auditLogger->log('notes_post_it', 'note.created', 'PostItNote', $note->getId(), $this->auditPayload($note));
    }

    protected function auditUpdated(PostItNoteInterface $note): void
    {
        $this->auditLogger->log('notes_post_it', 'note.updated', 'PostItNote', $note->getId(), $this->auditPayload($note));
    }

    protected function auditDeleted(PostItNoteInterface $note): void
    {
        $this->auditLogger->log('notes_post_it', 'note.deleted', 'PostItNote', $note->getId(), $this->auditPayload($note));
    }
}
