<?php

declare(strict_types=1);

namespace App\Contract;

use App\Entity\User;
use App\Enum\LocaleEnum;

interface UserManagerInterface
{
    public function create(string $name, string $email, string $password, bool $isAdmin = true): User;

    public function update(User $user, string $name, string $email): void;

    public function updateWithRole(User $user, string $name, string $email, string $role): void;

    public function toggleDevRole(User $user): bool;

    public function toggleDisabled(User $user): bool;

    public function changePassword(User $user, string $newPassword): void;

    public function changeLocale(User $user, LocaleEnum $locale): void;

    public function delete(User $user): void;

    public function isPasswordValid(User $user, string $plainPassword): bool;

    public function isEmailTaken(string $email, ?User $excludeUser = null): bool;

    public function invite(string $name, string $email, string $role, ?string $customMessage): User;

    public function resendInvitation(User $user, ?string $customMessage): void;

    public function consumeInvitation(User $user, string $plainPassword): void;

    public function findValidInvitation(string $selector, string $token): ?User;
}
