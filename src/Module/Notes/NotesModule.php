<?php

declare(strict_types=1);

namespace Aurora\Module\Notes;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Nav\NavItem;
use Aurora\Core\Module\Nav\NavPermission;
use Aurora\Core\Module\Nav\NavSection;

final readonly class NotesModule implements ModuleInterface
{
    public function getId(): string
    {
        return 'notes';
    }

    public function getPermissions(): array
    {
        return [
            new NavPermission('notes.markdown.use'),
        ];
    }

    public function getNavSections(): array
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
            ], priority: 25),
        ];
    }

    public function getCatalogNavSections(): array
    {
        return $this->getNavSections();
    }
}
