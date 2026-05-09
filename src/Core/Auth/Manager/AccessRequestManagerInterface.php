<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Manager;

use Aurora\Core\Auth\Entity\AccessRequest;
use Aurora\Core\Auth\Entity\AccessRequestInterface;

interface AccessRequestManagerInterface
{
    public function create(string $email, ?string $name, ?string $message): AccessRequestInterface;

    public function approve(AccessRequest $request, ?string $generatedPassword = null): void;

    public function reject(AccessRequest $request): void;
}
