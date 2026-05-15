<?php

declare(strict_types=1);

namespace Aurora\Module\Notes;

use Aurora\Core\Module\Contract\ModuleInterface;
use Aurora\Core\Module\Nav\NavPermission;

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
        return [];
    }

    public function getCatalogNavSections(): array
    {
        return [];
    }
}
