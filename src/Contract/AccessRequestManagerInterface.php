<?php

declare(strict_types=1);

namespace App\Contract;

use App\Entity\AccessRequest;

interface AccessRequestManagerInterface
{
    public function create(string $email, ?string $name, ?string $message): AccessRequest;

    public function approve(AccessRequest $request, ?string $generatedPassword = null): void;

    public function reject(AccessRequest $request): void;
}
