<?php

declare(strict_types=1);

namespace Aurora\Module\Dev\MountPoint\Service;

final readonly class MountPointTestResult
{
    private function __construct(
        public bool $success,
        public ?string $message,
    ) {}

    public static function success(?string $message = null): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
