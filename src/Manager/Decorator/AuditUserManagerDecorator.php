<?php

declare(strict_types=1);

namespace App\Manager\Decorator;

use App\Contract\UserManagerInterface;
use App\Entity\User;
use App\Enum\LocaleEnum;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

#[AsDecorator(decorates: UserManagerInterface::class)]
final readonly class AuditUserManagerDecorator implements UserManagerInterface
{
    public function __construct(
        #[AutowireDecorated]
        private UserManagerInterface $inner,
        private LoggerInterface $logger,
        private Security $security,
    ) {}

    public function create(string $name, string $email, string $password, bool $isAdmin = true): User
    {
        $user = $this->inner->create($name, $email, $password, $isAdmin);

        $this->logger->info('user.created', [
            'email' => $email,
            'isAdmin' => $isAdmin,
            'actor' => $this->actorEmail(),
        ]);

        return $user;
    }

    public function register(string $name, string $email, string $password): User
    {
        $user = $this->inner->register($name, $email, $password);

        $this->logger->info('user.registered', ['email' => $email]);

        return $user;
    }

    public function sendVerificationEmail(User $user): void
    {
        $this->inner->sendVerificationEmail($user);
    }

    public function verifyEmail(string $token): ?User
    {
        $user = $this->inner->verifyEmail($token);

        if ($user instanceof User) {
            $this->logger->info('user.email_verified', ['email' => $user->getEmail()]);
        }

        return $user;
    }

    public function resendVerificationEmail(string $email): void
    {
        $this->inner->resendVerificationEmail($email);
    }

    public function update(User $user, string $name, string $email): void
    {
        $this->inner->update($user, $name, $email);

        $this->logger->info('user.updated', [
            'userId' => $user->getId(),
            'email' => $email,
            'actor' => $this->actorEmail(),
        ]);
    }

    public function updateWithRole(User $user, string $name, string $email, string $role): void
    {
        $this->inner->updateWithRole($user, $name, $email, $role);

        $this->logger->info('user.role_updated', [
            'userId' => $user->getId(),
            'email' => $email,
            'role' => $role,
            'actor' => $this->actorEmail(),
        ]);
    }

    public function toggleDevRole(User $user): bool
    {
        $result = $this->inner->toggleDevRole($user);

        $this->logger->info('user.dev_role_toggled', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'devRoleEnabled' => $result,
            'actor' => $this->actorEmail(),
        ]);

        return $result;
    }

    public function toggleDisabled(User $user): bool
    {
        $result = $this->inner->toggleDisabled($user);

        $this->logger->warning('user.disabled_toggled', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'disabled' => !$result,
            'actor' => $this->actorEmail(),
        ]);

        return $result;
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $this->inner->changePassword($user, $newPassword);

        $this->logger->warning('user.password_changed', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'actor' => $this->actorEmail(),
        ]);
    }

    public function changeLocale(User $user, LocaleEnum $locale): void
    {
        $this->inner->changeLocale($user, $locale);
    }

    public function delete(User $user): void
    {
        $this->logger->warning('user.deleted', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'actor' => $this->actorEmail(),
        ]);

        $this->inner->delete($user);
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->inner->isPasswordValid($user, $plainPassword);
    }

    public function isEmailTaken(string $email, ?User $excludeUser = null): bool
    {
        return $this->inner->isEmailTaken($email, $excludeUser);
    }

    public function invite(string $name, string $email, string $role, ?string $customMessage): User
    {
        $user = $this->inner->invite($name, $email, $role, $customMessage);

        $this->logger->info('user.invited', [
            'email' => $email,
            'role' => $role,
            'actor' => $this->actorEmail(),
        ]);

        return $user;
    }

    public function resendInvitation(User $user, ?string $customMessage): void
    {
        $this->inner->resendInvitation($user, $customMessage);

        $this->logger->info('user.invitation_resent', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'actor' => $this->actorEmail(),
        ]);
    }

    public function consumeInvitation(User $user, string $plainPassword): void
    {
        $this->inner->consumeInvitation($user, $plainPassword);

        $this->logger->info('user.invitation_consumed', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
    }

    public function findValidInvitation(string $selector, string $token): ?User
    {
        return $this->inner->findValidInvitation($selector, $token);
    }

    public function canActOn(User $actor, User $target): bool
    {
        return $this->inner->canActOn($actor, $target);
    }

    private function actorEmail(): ?string
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user->getEmail() : null;
    }
}
