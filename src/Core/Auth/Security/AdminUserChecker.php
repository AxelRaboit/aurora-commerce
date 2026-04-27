<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Security;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserStatusEnum;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AdminUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void {}

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (UserStatusEnum::PendingVerification === $user->getStatus()) {
            throw new CustomUserMessageAccountStatusException('admin.errors.email_not_verified');
        }

        if (UserStatusEnum::Disabled === $user->getStatus()) {
            throw new CustomUserMessageAccountStatusException('admin.errors.account_disabled');
        }
    }
}
