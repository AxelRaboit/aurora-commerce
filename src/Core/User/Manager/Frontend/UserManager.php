<?php

declare(strict_types=1);

namespace Aurora\Core\User\Manager\Frontend;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Auth\Dto\Frontend\RegisterInput;
use Aurora\Core\Auth\Entity\ResetPasswordRequest;
use Aurora\Core\Auth\Manager\EmailVerificationManagerInterface;
use Aurora\Core\Auth\Manager\PasswordResetManagerInterface;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\CoreUserInterface;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Enum\UserStatusEnum;
use Aurora\Core\User\Enum\UserTypeEnum;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Core\User\Service\UserNotificationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsAlias(UserManagerInterface::class)]
class UserManager implements UserManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UserRepository $userRepository,
        protected readonly UserPasswordHasherInterface $passwordHasher,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly EmailVerificationManagerInterface $emailVerificationManager,
        protected readonly PasswordResetManagerInterface $passwordResetManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly UserNotificationService $notificationService,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {}

    public function register(RegisterInput $input): CoreUserInterface
    {
        $prefix = $this->settingRepository->get(ApplicationParameterEnum::CoreUserPrefix->value, SequencePrefixEnum::User->value) ?? SequencePrefixEnum::User->value;

        $user = $this->createUser();
        $user->setName($input->name);
        $user->setEmail($input->email);
        $user->setType(UserTypeEnum::Frontend);
        $user->setRoles([UserRoleEnum::User->value]);
        $user->setStatus(UserStatusEnum::PendingVerification);
        $user->setPassword($this->passwordHasher->hashPassword($user, $input->password));
        $user->setReference($this->sequenceGenerator->next($prefix));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->sendVerificationEmail($user, $input->locale);

        return $user;
    }

    public function sendVerificationEmail(CoreUserInterface $user, string $locale): void
    {
        $token = $this->emailVerificationManager->generateToken($user);

        $verifyUrl = $this->urlGenerator->generate('frontend_verify_email', [
            'token' => $token,
            'locale' => $locale,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->emailVerificationManager->sendVerificationEmail($user, $verifyUrl);
    }

    public function verifyEmail(string $token): ?CoreUserInterface
    {
        $user = $this->userRepository->findOneBy(['emailVerificationToken' => $token]);
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

    public function updateProfile(User $user, string $name, ?string $newPassword = null): void
    {
        $user->setName($name);
        if (null !== $newPassword && '' !== $newPassword) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        }

        $this->entityManager->flush();

        $this->auditLogger->log('core', 'frontend_user.profile_updated', 'User', $user->getId(), ['email' => $user->getEmail()]);
    }

    public function deleteAccount(User $user): void
    {
        $id = $user->getId();
        $email = $user->getEmail();
        $name = $user->getName();

        $this->notificationService->notifyAccountDeleted($email, $name, $user->getLocale()->value);

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->auditLogger->log('core', 'frontend_user.account_deleted', 'User', $id, ['email' => $email]);
    }

    public function resendVerificationEmail(string $email, string $locale): void
    {
        $user = $this->userRepository->findOneBy([
            'email' => $email,
            'type' => UserTypeEnum::Frontend,
        ]);

        if (!$user instanceof User || UserStatusEnum::PendingVerification !== $user->getStatus()) {
            return;
        }

        $this->sendVerificationEmail($user, $locale);
    }

    public function sendPasswordResetEmail(string $email, string $locale): void
    {
        $user = $this->userRepository->findOneBy([
            'email' => $email,
            'type' => UserTypeEnum::Frontend,
        ]);

        if (!$user instanceof User) {
            return;
        }

        ['selector' => $selector, 'plainToken' => $plainToken, 'expiresAt' => $expiresAt] = $this->passwordResetManager->createRequestForUser($user);

        $resetUrl = $this->urlGenerator->generate('frontend_reset_password', [
            'locale' => $locale,
            'selector' => $selector,
            'token' => $plainToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->passwordResetManager->sendResetEmail($user, $resetUrl, $expiresAt);
    }

    public function validateResetToken(string $selector, string $token): ?ResetPasswordRequest
    {
        return $this->passwordResetManager->validateToken($selector, $token, UserTypeEnum::Frontend);
    }

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void
    {
        $this->passwordResetManager->resetPassword($resetRequest, $newPassword);
    }

    protected function createUser(): CoreUserInterface
    {
        return new User();
    }
}
