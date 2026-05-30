<?php

declare(strict_types=1);

namespace Aurora\Module\Notes;

use Aurora\Core\Module\Service\ModuleAccessChecker;
use Aurora\Module\Notes\Setting\NotesModuleParameterEnum;

final readonly class NotesContext
{
    public function __construct(private ModuleAccessChecker $moduleAccessChecker) {}

    public function isBackendEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(NotesModuleParameterEnum::Backend->value);
    }

    public function isMarkdownEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(NotesModuleParameterEnum::Markdown->value);
    }

    public function isBlockEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(NotesModuleParameterEnum::Block->value);
    }

    public function isPostItEnabled(): bool
    {
        return $this->moduleAccessChecker->isEnabled(NotesModuleParameterEnum::PostIt->value);
    }
}
