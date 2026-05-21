<?php

declare(strict_types=1);

namespace Aurora\Module\Notes;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Contract\ModuleToggleProviderInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;
use Aurora\Module\Configuration\Setting\Enum\ModuleParameterEnum;

final readonly class NotesModule implements ModuleInterface, ModuleToggleProviderInterface
{
    public function __construct(private NotesContext $notesContext) {}

    public function getId(): string
    {
        return 'notes';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('notes.markdown.use'),
            new NavPermission('notes.block.use'),
            new NavPermission('notes.post_it.use'),
        ];
    }

    public function getNavSections(): array
    {
        if (!$this->notesContext->isBackendEnabled()) {
            return [];
        }

        $items = [];

        if ($this->notesContext->isMarkdownEnabled()) {
            $items[] = new NavItem(
                'backend_notes_markdown',
                'backend.nav.notes_markdown',
                'file-text',
                requiredPrivilege: 'notes.markdown.use',
                descriptionKey: 'backend.nav.notes_markdown_description',
            );
        }

        if ($this->notesContext->isBlockEnabled()) {
            $items[] = new NavItem(
                'backend_notes_block',
                'backend.nav.notes_block',
                'layers',
                requiredPrivilege: 'notes.block.use',
                descriptionKey: 'backend.nav.notes_block_description',
            );
        }

        if ($this->notesContext->isPostItEnabled()) {
            $items[] = new NavItem(
                'backend_notes_post_it',
                'backend.nav.notes_post_it',
                'sticky-note',
                requiredPrivilege: 'notes.post_it.use',
                descriptionKey: 'backend.nav.notes_post_it_description',
            );
        }

        if ([] === $items) {
            return [];
        }

        return [new NavSection('notes', $items, priority: 25)];
    }

    public function getCatalogNavSections(): array
    {
        return [
            new NavSection('notes', [
                new NavItem(
                    'backend_notes_markdown',
                    'backend.nav.notes_markdown',
                    'file-text',
                    requiredPrivilege: 'notes.markdown.use',
                    descriptionKey: 'backend.nav.notes_markdown_description',
                ),
                new NavItem(
                    'backend_notes_block',
                    'backend.nav.notes_block',
                    'layers',
                    requiredPrivilege: 'notes.block.use',
                    descriptionKey: 'backend.nav.notes_block_description',
                ),
                new NavItem(
                    'backend_notes_post_it',
                    'backend.nav.notes_post_it',
                    'sticky-note',
                    requiredPrivilege: 'notes.post_it.use',
                    descriptionKey: 'backend.nav.notes_post_it_description',
                ),
            ], priority: 25),
        ];
    }

    public function getToggles(): array
    {
        return [
            ModuleParameterEnum::NotesBackend->toToggle(),
            ModuleParameterEnum::NotesMarkdown->toToggle(),
            ModuleParameterEnum::NotesBlock->toToggle(),
            ModuleParameterEnum::NotesPostIt->toToggle(),
        ];
    }
}
