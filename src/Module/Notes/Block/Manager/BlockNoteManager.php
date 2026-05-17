<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Notes\Block\Dto\BlockInput;
use Aurora\Module\Notes\Block\Dto\BlockNoteInputInterface;
use Aurora\Module\Notes\Block\Entity\BlockNote;
use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;
use Aurora\Module\Notes\Block\Repository\BlockNoteRepository;
use Aurora\Module\Notes\Block\Service\BlockImageService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(BlockNoteManagerInterface::class)]
class BlockNoteManager implements BlockNoteManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly BlockNoteRepository $noteRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly BlockImageService $imageService,
    ) {}

    public function create(CoreUserInterface $user, BlockNoteInputInterface $input): BlockNoteInterface
    {
        $note = $this->createNote();
        $note->setUser($user);
        $note->setAgency($user->getAgency());

        $this->applyInput($note, $input);

        if (null === $input->getPosition()) {
            $note->setPosition($this->resolveInsertPosition($user, $input->getParentId()));
        }

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        $this->auditCreated($note);

        return $note;
    }

    public function update(BlockNoteInterface $note, BlockNoteInputInterface $input): void
    {
        $this->applyInput($note, $input);

        $this->entityManager->flush();

        $this->auditUpdated($note);
    }

    public function delete(BlockNoteInterface $note): void
    {
        $this->auditDeleted($note);

        $this->cleanupOrphanedAssets($note->getUser(), $note->getBlocks(), []);

        $this->entityManager->remove($note);
        $this->entityManager->flush();
    }

    public function move(BlockNoteInterface $note, ?BlockNoteInterface $parent): void
    {
        $note->setParent($parent);
        $this->entityManager->flush();
    }

    public function reorder(CoreUserInterface $user, array $entries): void
    {
        if ([] === $entries) {
            return;
        }

        $ids = array_map(static fn (array $entry): int => $entry['id'], $entries);
        $notes = $this->noteRepository->findBy(['id' => $ids, 'user' => $user]);

        $byId = [];
        foreach ($notes as $note) {
            $byId[$note->getId()] = $note;
        }

        $parentMap = [];
        foreach ($entries as $entry) {
            $id = (int) $entry['id'];
            if (!isset($byId[$id])) {
                continue;
            }

            $parentId = $entry['parentId'] ?? null;
            $parentMap[$id] = null === $parentId ? null : (int) $parentId;
        }

        foreach ($parentMap as $id => $initialParentId) {
            $visited = [$id => true];
            $current = $initialParentId;
            while (null !== $current) {
                if (isset($visited[$current])) {
                    throw new InvalidArgumentException(sprintf('Reorder would create a cycle at note %d.', $id));
                }

                $visited[$current] = true;
                $current = $parentMap[$current] ?? null;
            }
        }

        foreach (array_keys($parentMap) as $id) {
            $byId[$id]->setParent(null);
        }

        foreach ($entries as $entry) {
            $id = (int) $entry['id'];
            $note = $byId[$id] ?? null;
            if (null === $note) {
                continue;
            }

            $parentId = $parentMap[$id] ?? null;
            $note->setParent(null !== $parentId ? ($byId[$parentId] ?? null) : null);
            $note->setPosition((int) $entry['position']);
        }

        $this->entityManager->flush();
    }

    public function tagCounts(CoreUserInterface $user): array
    {
        return $this->noteRepository->findTagCountsForUser($user);
    }

    public function searchContent(CoreUserInterface $user, string $query): array
    {
        $needle = mb_strtolower(mb_trim($query));
        if ('' === $needle) {
            return [];
        }

        $matches = [];
        foreach ($this->noteRepository->findAllForUser($user) as $note) {
            if ($this->noteMatches($note, $needle)) {
                $matches[] = $note->getId();
            }
        }

        return $matches;
    }

    public function renameTag(CoreUserInterface $user, string $oldTag, string $newTag): int
    {
        $oldTag = mb_trim($oldTag);
        $newTag = mb_trim($newTag);
        if ('' === $oldTag || '' === $newTag || $oldTag === $newTag) {
            return 0;
        }

        $affected = $this->rewriteTags($user, [$oldTag => $newTag]);
        if ($affected > 0) {
            $this->entityManager->flush();
            $this->auditTagsOperation('renamed', [...$this->auditTagsPayload(), 'old' => $oldTag, 'new' => $newTag, 'notes' => $affected]);
        }

        return $affected;
    }

    public function mergeTags(CoreUserInterface $user, array $sourceTags, string $targetTag): int
    {
        $targetTag = mb_trim($targetTag);
        if ('' === $targetTag) {
            return 0;
        }

        $rewrite = [];
        foreach ($sourceTags as $source) {
            $trimmed = mb_trim($source);
            if ('' === $trimmed) {
                continue;
            }

            if ($trimmed === $targetTag) {
                continue;
            }

            $rewrite[$trimmed] = $targetTag;
        }

        if ([] === $rewrite) {
            return 0;
        }

        $affected = $this->rewriteTags($user, $rewrite);
        if ($affected > 0) {
            $this->entityManager->flush();
            $this->auditTagsOperation('merged', [...$this->auditTagsPayload(), 'sources' => array_keys($rewrite), 'target' => $targetTag, 'notes' => $affected]);
        }

        return $affected;
    }

    public function removeTag(CoreUserInterface $user, string $tag): int
    {
        $tag = mb_trim($tag);
        if ('' === $tag) {
            return 0;
        }

        $affected = 0;
        foreach ($this->noteRepository->findAllForUser($user) as $note) {
            $tags = $note->getTags();
            if (!in_array($tag, $tags, true)) {
                continue;
            }

            $note->setTags(array_values(array_filter($tags, static fn (string $existing): bool => $existing !== $tag)));
            ++$affected;
        }

        if ($affected > 0) {
            $this->entityManager->flush();
            $this->auditTagsOperation('removed', [...$this->auditTagsPayload(), 'tag' => $tag, 'notes' => $affected]);
        }

        return $affected;
    }

    /**
     * @param array<string, string> $rewrite source → target
     */
    protected function rewriteTags(CoreUserInterface $user, array $rewrite): int
    {
        $affected = 0;
        foreach ($this->noteRepository->findAllForUser($user) as $note) {
            $current = $note->getTags();
            $next = [];
            $seen = [];
            $changed = false;

            foreach ($current as $tag) {
                $resolved = $rewrite[$tag] ?? $tag;
                if ($resolved !== $tag) {
                    $changed = true;
                }

                if (isset($seen[$resolved])) {
                    $changed = true;
                    continue;
                }

                $seen[$resolved] = true;
                $next[] = $resolved;
            }

            if ($changed) {
                $note->setTags($next);
                ++$affected;
            }
        }

        return $affected;
    }

    /**
     * Hook: instantiate the concrete note. Clients override to substitute.
     */
    protected function createNote(): BlockNoteInterface
    {
        return new BlockNote();
    }

    /**
     * Hook: pick the position of a freshly-created note when the input
     * doesn't carry one. Default strategy appends at the end of the
     * sibling list (max + 1, or 0 when empty). Clients override to
     * implement different ordering strategies (e.g. prepend at top).
     */
    protected function resolveInsertPosition(CoreUserInterface $user, ?int $parentId): int
    {
        $max = $this->noteRepository->findMaxPositionForUserAndParent($user, $parentId);

        return null === $max ? 0 : $max + 1;
    }

    /**
     * Hook: hydrate the note from the DTO — including the blocks array,
     * which lives on the note as a JSON column. Clients override to add
     * custom fields with `parent::applyInput(...)` then their own setters.
     */
    protected function applyInput(BlockNoteInterface $note, BlockNoteInputInterface $input): void
    {
        $note->setTitle($input->getTitle());
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

        $blocks = $input->getBlocks();
        if (null !== $blocks) {
            $next = $this->normalizeBlocks($blocks);
            $this->cleanupOrphanedAssets($note->getUser(), $note->getBlocks(), $next);
            $note->setBlocks($next);
        }
    }

    /**
     * Hook: convert the validated DTO list into the JSON-storable shape
     * (Editor.js' native `{id?, type, data}` map). Identity is the
     * Editor.js-generated id; order is the array order. Clients override
     * to add per-type normalisation.
     *
     * @param list<BlockInput> $blocks
     *
     * @return list<array{id?: string, type: string, data: array<string, mixed>}>
     */
    protected function normalizeBlocks(array $blocks): array
    {
        return array_map(static function (BlockInput $b): array {
            $entry = ['type' => $b->type, 'data' => $b->data];
            if (null !== $b->id) {
                return ['id' => $b->id, ...$entry];
            }

            return $entry;
        }, $blocks);
    }

    /**
     * Diff old vs new block lists and release external assets (uploaded
     * images, generated PDFs…) attached to blocks that disappeared. The
     * default implementation handles the built-in `image` type — clients
     * override (chain via parent::) to free their own custom artefacts.
     *
     * Editor.js' Image tool stores the upload response under `data.file`
     * (we ship a custom `filename` field inside that object, see the
     * `uploadByFile` adapter in NotesBlockEditor.vue), so we look it up
     * via {@see imageFilename()} which a client can override.
     *
     * @param list<array{id?: string, type: string, data: array<string, mixed>}> $previous
     * @param list<array{id?: string, type: string, data: array<string, mixed>}> $next
     */
    protected function cleanupOrphanedAssets(CoreUserInterface $user, array $previous, array $next): void
    {
        $keptFilenames = [];
        foreach ($next as $block) {
            $filename = $this->imageFilename($block);
            if (null !== $filename) {
                $keptFilenames[$filename] = true;
            }
        }

        foreach ($previous as $block) {
            $filename = $this->imageFilename($block);
            if (null === $filename) {
                continue;
            }

            if (isset($keptFilenames[$filename])) {
                continue;
            }

            $this->imageService->delete($filename, $user);
        }
    }

    /**
     * Hook: extract the per-user upload filename from a block payload, or
     * null if this block does not own a deletable image asset. Default
     * implementation reads `data.file.filename` on `image` blocks (the
     * shape produced by Editor.js' Image tool with our upload adapter).
     *
     * @param array{id?: string, type: string, data: array<string, mixed>} $block
     */
    protected function imageFilename(array $block): ?string
    {
        if ('image' !== $block['type']) {
            return null;
        }

        $file = $block['data']['file'] ?? null;
        if (!is_array($file)) {
            return null;
        }

        $filename = $file['filename'] ?? null;

        return is_string($filename) && '' !== $filename ? $filename : null;
    }

    /**
     * Recursive walk: returns true if any string value reachable from
     * `$payload` contains `$needle`. Editor.js block shapes are nested
     * and tool-dependent (paragraph.text, header.text, list.items[*].
     * content, quote.text, code.code, table.content[*][*], callout.
     * title/message, image.caption…), so we don't hard-code field names.
     */
    private function payloadContains(mixed $payload, string $needle): bool
    {
        if (is_string($payload)) {
            return '' !== $payload && str_contains(mb_strtolower(strip_tags($payload)), $needle);
        }

        if (is_array($payload)) {
            foreach ($payload as $value) {
                if ($this->payloadContains($value, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function noteMatches(BlockNoteInterface $note, string $needle): bool
    {
        $title = $note->getTitle();
        if (null !== $title && '' !== $title && str_contains(mb_strtolower($title), $needle)) {
            return true;
        }

        return array_any($note->getBlocks(), fn (array $block): bool => $this->payloadContains($block['data'], $needle));
    }

    /**
     * Hook: base payload merged into every tag-operation audit log.
     *
     * @return array<string, mixed>
     */
    protected function auditTagsPayload(): array
    {
        return [];
    }

    protected function auditTagsOperation(string $action, array $payload): void
    {
        $this->auditLogger->log('notes_block', 'tag.'.$action, 'BlockNote', null, $payload);
    }

    protected function auditCreated(BlockNoteInterface $note): void
    {
        $this->auditLogger->log('notes_block', 'note.created', 'BlockNote', $note->getId(), $this->auditPayload($note));
    }

    protected function auditUpdated(BlockNoteInterface $note): void
    {
        $this->auditLogger->log('notes_block', 'note.updated', 'BlockNote', $note->getId(), $this->auditPayload($note));
    }

    protected function auditDeleted(BlockNoteInterface $note): void
    {
        $this->auditLogger->log('notes_block', 'note.deleted', 'BlockNote', $note->getId(), $this->auditPayload($note));
    }

    /**
     * Hook: build the audit payload. Clients override to splat-merge custom
     * fields, e.g.
     *   return [...parent::auditPayload($note), 'custom' => $note->getCustom()];.
     *
     * @return array<string, mixed>
     */
    protected function auditPayload(BlockNoteInterface $note): array
    {
        return [
            'title' => $note->getTitle(),
            'tags' => $note->getTags(),
            'parentId' => $note->getParent()?->getId(),
            'position' => $note->getPosition(),
            'blocks' => count($note->getBlocks()),
        ];
    }
}
