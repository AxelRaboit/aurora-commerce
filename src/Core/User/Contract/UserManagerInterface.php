<?php

declare(strict_types=1);

namespace Aurora\Core\User\Contract;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\User\Entity\User;

interface UserManagerInterface
{
    public function create(string $name, string $email, string $password, bool $isAdmin = true): User;

    public function register(string $name, string $email, string $password): User;

    public function sendVerificationEmail(User $user): void;

    public function verifyEmail(string $token): ?User;

    public function resendVerificationEmail(string $email): void;

    public function update(User $user, string $name, string $email): void;

    public function updateWithRole(User $user, string $name, string $email, string $role, ?string $password = null): void;

    public function toggleDevRole(User $user): bool;

    public function toggleDisabled(User $user): bool;

    public function changePassword(User $user, string $newPassword): void;

    public function changeLocaleEnum(User $user, LocaleEnum $locale): void;

    public function changeMoodMessage(User $user, ?string $moodMessage): void;

    public function updateAgencyAndService(User $user, ?int $agencyId, ?int $serviceId): void;

    /** @param list<string> $privileges */
    public function updatePrivileges(User $user, array $privileges): void;

    public function delete(User $user): void;

    public function isPasswordValid(User $user, string $plainPassword): bool;

    public function isEmailTaken(string $email, ?User $excludeUser = null): bool;

    public function invite(string $name, string $email, string $role, ?string $customMessage): User;

    public function resendInvitation(User $user, ?string $customMessage): void;

    public function consumeInvitation(User $user, string $plainPassword): void;

    public function findValidInvitation(string $selector, string $token): ?User;

    public function canActOn(User $actor, User $target): bool;
}
