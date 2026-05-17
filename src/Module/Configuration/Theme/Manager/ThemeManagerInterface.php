<?php

declare(strict_types=1);

namespace Aurora\Module\Configuration\Theme\Manager;

use Aurora\Module\Configuration\Theme\Dto\ThemeInputInterface;
use Aurora\Module\Configuration\Theme\Entity\ThemeInterface;

interface ThemeManagerInterface
{
    public function create(ThemeInputInterface $input): ThemeInterface;

    public function update(ThemeInterface $theme, ThemeInputInterface $input): void;

    public function activate(ThemeInterface $theme): void;

    public function delete(ThemeInterface $theme): void;

    public function countTemplates(string $slug): int;
}
