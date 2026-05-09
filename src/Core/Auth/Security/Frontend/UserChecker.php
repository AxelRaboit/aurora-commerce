<?php

declare(strict_types=1);

namespace Aurora\Core\Auth\Security\Frontend;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserStatusEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void {}

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (UserStatusEnum::PendingVerification === $user->getStatus()) {
            throw new CustomUserMessageAccountStatusException('frontend.errors.email_not_verified');
        }

        if (UserStatusEnum::Disabled === $user->getStatus()) {
            throw new CustomUserMessageAccountStatusException('frontend.errors.account_disabled');
        }
    }
}
