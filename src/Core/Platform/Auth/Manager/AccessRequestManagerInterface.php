<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Auth\Manager;

use Aurora\Core\Platform\Auth\Entity\AccessRequest;
use Aurora\Core\Platform\Auth\Entity\AccessRequestInterface;

interface AccessRequestManagerInterface
{
    public function create(string $email, ?string $name, ?string $message): AccessRequestInterface;

    public function approve(AccessRequest $request, ?string $generatedPassword = null): void;

    public function reject(AccessRequest $request): void;
}
