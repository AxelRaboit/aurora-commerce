<?php

declare(strict_types=1);

namespace Aurora\Core\User\Manager;

use Aurora\Core\Auth\DTO\FrontRegisterInput;
use Aurora\Core\Auth\Entity\ResetPasswordRequest;
use Aurora\Core\Auth\Manager\EmailVerificationManager;
use Aurora\Core\Auth\Manager\PasswordResetManager;
use Aurora\Core\User\Contract\FrontUserManagerInterface;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Enum\UserRoleEnum;
use Aurora\Core\User\Enum\UserStatusEnum;
use Aurora\Core\User\Enum\UserTypeEnum;
use Aurora\Core\User\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsAlias(FrontUserManagerInterface::class)]
final readonly class FrontUserManager implements FrontUserManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private UrlGeneratorInterface $urlGenerator,
        private EmailVerificationManager $emailVerificationManager,
        private PasswordResetManager $passwordResetManager,
    ) {}

    public function register(FrontRegisterInput $input): User
    {
        $user = new User();
        $user->setName($input->name);
        $user->setEmail($input->email);
        $user->setType(UserTypeEnum::FrontUser);
        $user->setRoles([UserRoleEnum::User->value]);
        $user->setStatus(UserStatusEnum::PendingVerification);
        $user->setPassword($this->passwordHasher->hashPassword($user, $input->password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->sendVerificationEmail($user, $input->locale);

        return $user;
    }

    public function sendVerificationEmail(User $user, string $locale = 'fr'): void
    {
        $token = $this->emailVerificationManager->generateToken($user);

        $verifyUrl = $this->urlGenerator->generate('front_verify_email', [
            'token' => $token,
            'locale' => $locale,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->emailVerificationManager->sendVerificationEmail($user, $verifyUrl);
    }

    public function verifyEmail(string $token): ?User
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
    }

    public function deleteAccount(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function resendVerificationEmail(string $email, string $locale): void
    {
        $user = $this->userRepository->findOneBy([
            'email' => $email,
            'type' => UserTypeEnum::FrontUser,
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
            'type' => UserTypeEnum::FrontUser,
        ]);

        if (!$user instanceof User) {
            return;
        }

        ['selector' => $selector, 'plainToken' => $plainToken, 'expiresAt' => $expiresAt] = $this->passwordResetManager->createRequestForUser($user);

        $resetUrl = $this->urlGenerator->generate('front_reset_password', [
            'locale' => $locale,
            'selector' => $selector,
            'token' => $plainToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $this->passwordResetManager->sendResetEmail($user, $resetUrl, $expiresAt);
    }

    public function validateResetToken(string $selector, string $token): ?ResetPasswordRequest
    {
        return $this->passwordResetManager->validateToken($selector, $token, UserTypeEnum::FrontUser);
    }

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void
    {
        $this->passwordResetManager->resetPassword($resetRequest, $newPassword);
    }
}
