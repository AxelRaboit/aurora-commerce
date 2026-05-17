<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Service;

use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;

final readonly class BlockNoteHierarchyService
{
    /**
     * True if moving $note under $newParent would create a cycle, i.e.
     * $newParent is $note itself or a descendant of $note.
     */
    public function wouldCreateCycle(BlockNoteInterface $note, BlockNoteInterface $newParent): bool
    {
        $current = $newParent;
        while ($current instanceof BlockNoteInterface) {
            if ($current->getId() === $note->getId()) {
                return true;
            }

            $current = $current->getParent();
        }

        return false;
    }
}
