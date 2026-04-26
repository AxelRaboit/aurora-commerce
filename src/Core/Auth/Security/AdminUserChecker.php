<?php

declare(strict_types=1);

namespace App\Core\Auth\Security;

use App\Core\User\Entity\User;
use App\Core\User\Enum\UserStatusEnum;
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
