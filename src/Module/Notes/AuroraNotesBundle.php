<?php

declare(strict_types=1);

namespace Aurora\Module\Notes;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Notes\Block\Entity\BlockNote;
use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNote;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\Notes\PostIt\Entity\PostItNote;
use Aurora\Module\Notes\PostIt\Entity\PostItNoteInterface;

/** Self-contained bundle for the Notes module. @see AbstractAuroraModuleBundle */
final class AuroraNotesBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Notes';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            MarkdownNoteInterface::class => MarkdownNote::class,
            BlockNoteInterface::class => BlockNote::class,
            PostItNoteInterface::class => PostItNote::class,
        ];
    }
}
