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
    /** Matches [[anything]] occurrences in markdown content. */
    protected const string WIKI_LINK_REGEX = '/\[\[([^\]]+)\]\]/';

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
        $oldTitle = $note->getTitle();

        $this->applyInput($note, $input);

        $newTitle = $note->getTitle();
        if (null !== $oldTitle && null !== $newTitle && '' !== $oldTitle && $oldTitle !== $newTitle) {
            $this->renameWikiLinks($note->getUser(), $note->getId(), $oldTitle, $newTitle);
        }

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

    public function backlinks(CoreUserInterface $user, MarkdownNoteInterface $note): array
    {
        $title = $note->getTitle();
        if (null === $title || '' === $title) {
            return [];
        }

        $needle = '[['.mb_strtolower($title).']]';
        $results = [];

        foreach ($this->noteRepository->findAllWithContentForUser($user) as $other) {
            if ($other->getId() === $note->getId()) {
                continue;
            }
            $content = $other->getContent();
            if (null === $content || '' === $content) {
                continue;
            }
            if (!str_contains(mb_strtolower($content), $needle)) {
                continue;
            }
            $results[] = ['id' => $other->getId(), 'title' => $other->getTitle()];
        }

        return $results;
    }

    public function unlinkedMentions(CoreUserInterface $user, MarkdownNoteInterface $note): array
    {
        $title = $note->getTitle();
        if (null === $title || '' === $title) {
            return [];
        }

        $titleLower = mb_strtolower($title);
        $linkedPattern = '[['.$titleLower.']]';
        $results = [];

        foreach ($this->noteRepository->findAllWithContentForUser($user) as $other) {
            if ($other->getId() === $note->getId()) {
                continue;
            }
            $content = $other->getContent();
            if (null === $content || '' === $content) {
                continue;
            }
            $contentLower = mb_strtolower($content);
            if (!str_contains($contentLower, $titleLower)) {
                continue;
            }
            if (str_contains($contentLower, $linkedPattern)) {
                continue;
            }
            $results[] = ['id' => $other->getId(), 'title' => $other->getTitle()];
        }

        return $results;
    }

    public function graph(CoreUserInterface $user): array
    {
        $notes = $this->noteRepository->findAllWithContentForUser($user);

        $titleToId = [];
        $nodes = [];
        foreach ($notes as $note) {
            $title = $note->getTitle() ?? '';
            $nodes[] = ['id' => $note->getId(), 'title' => '' === $title ? 'Untitled' : $title];
            if ('' !== $title) {
                $titleToId[mb_strtolower($title)] = $note->getId();
            }
        }

        $edges = [];
        foreach ($notes as $note) {
            $content = $note->getContent();
            if (null === $content || '' === $content) {
                continue;
            }
            if (0 === preg_match_all(self::WIKI_LINK_REGEX, $content, $matches)) {
                continue;
            }
            foreach ($matches[1] as $rawTarget) {
                // strip anchor (#heading) — `[[Title#section]]` still points to "Title"
                $target = mb_strtolower(explode('#', (string) $rawTarget)[0]);
                if ('' === $target || !isset($titleToId[$target])) {
                    continue;
                }
                $targetId = $titleToId[$target];
                if ($targetId === $note->getId()) {
                    continue;
                }
                $edges[] = ['source' => $note->getId(), 'target' => $targetId];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * When a note's title changes, rewrite [[oldTitle]] → [[newTitle]] in all
     * the user's other notes. Case-sensitive substring (matches Onyx).
     * Clients override to customize the wiki-link syntax.
     */
    protected function renameWikiLinks(CoreUserInterface $user, ?int $excludeId, string $oldTitle, string $newTitle): void
    {
        $oldPattern = '[['.$oldTitle.']]';
        $newPattern = '[['.$newTitle.']]';

        foreach ($this->noteRepository->findAllWithContentForUser($user) as $other) {
            if (null !== $excludeId && $other->getId() === $excludeId) {
                continue;
            }
            $content = $other->getContent();
            if (null === $content || !str_contains($content, $oldPattern)) {
                continue;
            }
            $other->setContent(str_replace($oldPattern, $newPattern, $content));
        }
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
