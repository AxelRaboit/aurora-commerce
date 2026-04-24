<?php

declare(strict_types=1);

namespace App\Manager;

use App\Contract\UserManagerInterface;
use App\Entity\User;
use App\Enum\LocaleEnum;
use App\Enum\UserRoleEnum;
use App\Enum\UserStatusEnum;
use App\Repository\UserRepository;
use App\Service\InvitationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsAlias(UserManagerInterface::class)]
final readonly class UserManager implements UserManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private InvitationService $invitationService,
    ) {}

    public function create(string $name, string $email, string $password, bool $isAdmin = true): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles($isAdmin ? [UserRoleEnum::Admin->value] : []);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function update(User $user, string $name, string $email): void
    {
        $user->setName($name);
        $user->setEmail($email);

        $this->entityManager->flush();
    }

    public function updateWithRole(User $user, string $name, string $email, string $role): void
    {
        if (!in_array($role, UserRoleEnum::allAssignableValues(), true)) {
            throw new InvalidArgumentException('admin.users.errors.role_invalid');
        }

        $user->setName($name);
        $user->setEmail($email);

        $user->setRoles([$role]);

        $this->entityManager->flush();
    }

    public function toggleDevRole(User $user): bool
    {
        $hasDev = in_array(UserRoleEnum::Dev->value, $user->getRoles(), true);

        $user->setRoles($hasDev ? [UserRoleEnum::Admin->value] : [UserRoleEnum::Dev->value]);
        $this->entityManager->flush();

        return !$hasDev;
    }

    public function toggleDisabled(User $user): bool
    {
        $isDisabled = UserStatusEnum::Disabled === $user->getStatus();
        $user->setStatus($isDisabled ? UserStatusEnum::Active : UserStatusEnum::Disabled);

        $this->entityManager->flush();

        return !$isDisabled;
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $this->entityManager->flush();
    }

    public function changeLocale(User $user, LocaleEnum $locale): void
    {
        $user->setLocale($locale);
        $this->entityManager->flush();
    }

    public function delete(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    public function isEmailTaken(string $email, ?User $excludeUser = null): bool
    {
        $existing = $this->userRepository->findOneBy(['email' => $email]);

        if (null === $existing) {
            return false;
        }

        return !$excludeUser instanceof User || $existing->getId() !== $excludeUser->getId();
    }

    public function invite(string $name, string $email, string $role, ?string $customMessage): User
    {
        if (!in_array($role, UserRoleEnum::allAssignableValues(), true)) {
            throw new InvalidArgumentException('admin.users.errors.role_invalid');
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setRoles([$role]);
        $user->setStatus(UserStatusEnum::Invited);
        $user->setLocale(LocaleEnum::French);
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(24))));

        $plainToken = $this->prepareInvitationToken($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->invitationService->sendInvitation($user, $plainToken, $customMessage);

        return $user;
    }

    public function resendInvitation(User $user, ?string $customMessage): void
    {
        $user->setStatus(UserStatusEnum::Invited);
        $plainToken = $this->prepareInvitationToken($user);

        $this->entityManager->flush();

        $this->invitationService->sendInvitation($user, $plainToken, $customMessage);
    }

    public function consumeInvitation(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setStatus(UserStatusEnum::Active);
        $user->setInvitationSelector(null);
        $user->setInvitationHashedToken(null);
        $user->setInvitationExpiresAt(null);

        $this->entityManager->flush();
    }

    public function findValidInvitation(string $selector, string $token): ?User
    {
        $user = $this->userRepository->findByInvitationSelector($selector);

        if (!$user instanceof User) {
            return null;
        }

        if (!$user->isInvited()) {
            return null;
        }

        $expiresAt = $user->getInvitationExpiresAt();
        if (!$expiresAt instanceof DateTimeImmutable || $expiresAt < new DateTimeImmutable()) {
            return null;
        }

        $storedHash = $user->getInvitationHashedToken();
        if (null === $storedHash) {
            return null;
        }

        if (!hash_equals($storedHash, hash('sha256', $token))) {
            return null;
        }

        return $user;
    }

    private function prepareInvitationToken(User $user): string
    {
        $selector = bin2hex(random_bytes(10));
        $plainToken = bin2hex(random_bytes(32));

        $user->setInvitationSelector($selector);
        $user->setInvitationHashedToken(hash('sha256', $plainToken));
        $user->setInvitationExpiresAt(new DateTimeImmutable('+48 hours'));
        $user->setInvitedAt(new DateTimeImmutable());

        return $plainToken;
    }
}
