<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Service;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;

final readonly class ImpersonationTokenService
{
    private const int TTL_SECONDS = 30;

    public function __construct(
        private string $secret,
        private UserRepository $userRepository,
    ) {}

    public function generate(User $target): string
    {
        $payload = $target->getId().'|'.time();

        return $payload.'|'.hash_hmac('sha256', $payload, $this->secret);
    }

    public function validate(string $token): ?User
    {
        $parts = explode('|', $token);
        if (3 !== count($parts)) {
            return null;
        }

        [$id, $timestamp, $signature] = $parts;
        $payload = $id.'|'.$timestamp;

        if (!hash_equals(hash_hmac('sha256', $payload, $this->secret), $signature)) {
            return null;
        }

        if ((time() - (int) $timestamp) > self::TTL_SECONDS) {
            return null;
        }

        $user = $this->userRepository->find((int) $id);
        if (!$user instanceof User || !$user->isFrontUser()) {
            return null;
        }

        return $user;
    }
}
