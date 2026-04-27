<?php

declare(strict_types=1);

namespace Aurora\Core\Module;

final readonly class NavSection
{
    /**
     * @param NavItem[] $items
     * @param int       $priority Lower = renders first. Defaults to 100. Use higher values (e.g. 1000)
     *                            for system/meta sections that should sit at the bottom of the nav.
     */
    public function __construct(
        public string $id,
        public array $items,
        public int $priority = 100,
    ) {}
}
