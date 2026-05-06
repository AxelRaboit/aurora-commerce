<?php

declare(strict_types=1);

namespace Aurora\Core\User\Manager;

use Aurora\Core\Agency\Entity\Agency;
use Aurora\Core\Agency\Repository\AgencyRepository;
use Aurora\Core\Auth\Manager\EmailVerificationManager;
use Aurora\Core\Auth\Manager\InvitationManager;
use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Service\Entity\Service;
use Aurora\Core\Service\Repository\ServiceRepository;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\User\Contract\UserManagerInterface;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Enum\UserStatusEnum;
use Aurora\Core\User\Enum\UserTypeEnum;
use Aurora\Core\User\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsAlias(UserManagerInterface::class)]
final readonly class UserManager implements UserManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private InvitationManager $invitationManager,
        private UrlGeneratorInterface $urlGenerator,
        private EmailVerificationManager $emailVerificationManager,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
        private AgencyRepository $agencyRepository,
        private ServiceRepository $serviceRepository,
    ) {}

    public function create(string $name, string $email, string $password, bool $isAdmin = true): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setType(UserTypeEnum::Backend);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles($isAdmin ? [UserRoleEnum::Admin->value] : []);

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CoreUserPrefix->value, SequencePrefixEnum::User->value) ?? SequencePrefixEnum::User->value;
        $user->setReference($this->sequenceGenerator->next($prefix));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function register(string $name, string $email, string $password): User
    {
        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setType(UserTypeEnum::Backend);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles([UserRoleEnum::Admin->value]);
        $user->setStatus(UserStatusEnum::PendingVerification);

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CoreUserPrefix->value, SequencePrefixEnum::User->value) ?? SequencePrefixEnum::User->value;
        $user->setReference($this->sequenceGenerator->next($prefix));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->sendVerificationEmail($user);

        return $user;
    }

    public function sendVerificationEmail(User $user): void
    {
        $token = $this->emailVerificationManager->generateToken($user);

        $verifyUrl = $this->urlGenerator->generate('backend_verify_email', [
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->emailVerificationManager->sendVerificationEmail($user, $verifyUrl);
    }

    public function verifyEmail(string $token): ?User
    {
        $user = $this->userRepository->findOneBy(['emailVerificationToken' => $token, 'type' => UserTypeEnum::Backend]);
        if (null === $user) {
            return null;
        }

        $expiresAt = $user->getEmailVerificationExpiresAt();
        if (null === $expiresAt || $expiresAt < new DateTimeImmutable()) {
            return null;
        }

        $user->setStatus(UserStatusEnum::Active);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationExpiresAt(null);

        $this->entityManager->flush();

        return $user;
    }

    public function resendVerificationEmail(string $email): void
    {
        $user = $this->userRepository->findOneBy([
            'email' => $email,
            'type' => UserTypeEnum::Backend,
        ]);

        if (!$user instanceof User || UserStatusEnum::PendingVerification !== $user->getStatus()) {
            return;
        }

        $this->sendVerificationEmail($user);
    }

    public function update(User $user, string $name, string $email): void
    {
        if ($this->isEmailTaken($email, $user)) {
            throw new InvalidArgumentException('backend.users.errors.email_taken');
        }

        $user->setName($name);
        $user->setEmail($email);

        $this->entityManager->flush();
    }

    public function updateWithRole(User $user, string $name, string $email, string $role, ?string $password = null): void
    {
        if (!in_array($role, UserRoleEnum::allAssignableValues(), true)) {
            throw new InvalidArgumentException('backend.users.errors.role_invalid');
        }

        if ($this->isEmailTaken($email, $user)) {
            throw new InvalidArgumentException('backend.users.errors.email_taken');
        }

        $user->setName($name);
        $user->setEmail($email);
        $user->setRoles([$role]);

        if (null !== $password && '' !== $password) {
            $this->changePassword($user, $password);
        }

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

    public function changeLocaleEnum(User $user, LocaleEnum $locale): void
    {
        $user->setLocale($locale);
        $this->entityManager->flush();
    }

    public function changeMoodMessage(User $user, ?string $moodMessage): void
    {
        $user->setMoodMessage($moodMessage);
        $this->entityManager->flush();
    }

    public function updateAgencyAndService(User $user, ?int $agencyId, ?int $serviceId): void
    {
        $agency = null !== $agencyId ? $this->agencyRepository->find($agencyId) : null;
        $service = null !== $serviceId ? $this->serviceRepository->find($serviceId) : null;

        $user->setAgency($agency instanceof Agency ? $agency : null);
        $user->setService($service instanceof Service ? $service : null);

        $this->entityManager->flush();
    }

    /** @param list<string> $privileges */
    public function updatePrivileges(User $user, array $privileges): void
    {
        $user->setPrivileges($privileges);
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
            throw new InvalidArgumentException('backend.users.errors.role_invalid');
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setType(UserTypeEnum::Backend);
        $user->setRoles([$role]);
        $user->setStatus(UserStatusEnum::Invited);
        $user->setLocale(LocaleEnum::French);
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(24))));

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CoreUserPrefix->value, SequencePrefixEnum::User->value) ?? SequencePrefixEnum::User->value;
        $user->setReference($this->sequenceGenerator->next($prefix));

        $plainToken = $this->prepareInvitationToken($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->invitationManager->sendInvitation($user, $plainToken, $customMessage);

        return $user;
    }

    public function resendInvitation(User $user, ?string $customMessage): void
    {
        $user->setStatus(UserStatusEnum::Invited);
        $plainToken = $this->prepareInvitationToken($user);

        $this->entityManager->flush();

        $this->invitationManager->sendInvitation($user, $plainToken, $customMessage);
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

    public function canActOn(User $actor, User $target): bool
    {
        return UserRoleEnum::highestPriorityForRoles($actor->getRoles())
            >= UserRoleEnum::highestPriorityForRoles($target->getRoles());
    }
}
