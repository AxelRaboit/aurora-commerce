<?php

declare(strict_types=1);

namespace Aurora\Core\Module;

final readonly class NavPermission
{
    public function __construct(
        public string $name,
        public string $requiredRole,
    ) {}
}
