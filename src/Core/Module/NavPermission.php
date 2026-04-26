<?php

declare(strict_types=1);

namespace App\Core\Module;

final readonly class NavPermission
{
    public function __construct(
        public string $name,
        public string $requiredRole,
    ) {}
}
