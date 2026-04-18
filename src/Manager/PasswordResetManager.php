<?php

declare(strict_types=1);

namespace App\Manager;

use App\Contract\PasswordResetManagerInterface;
use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class PasswordResetManager implements PasswordResetManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ResetPasswordRequestRepository $resetRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $mailerFrom,
    ) {}

    public function sendResetLink(string $email): void
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            return;
        }

        $this->resetRepo->deleteByUser($user);

        $selector = bin2hex(random_bytes(10));
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        $resetRequest = new ResetPasswordRequest($user, $selector, $hashedToken, new DateTimeImmutable('+1 hour'));
        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();

        $resetUrl = $this->urlGenerator->generate('app_reset_password', [
            'selector' => $selector,
            'token' => $plainToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $html = sprintf(
            '<p>Bonjour %s,</p><p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe. Ce lien expire dans 1 heure.</p><p><a href="%s">%s</a></p><p>Si vous n\'avez pas demandé de réinitialisation, ignorez cet e-mail.</p>',
            htmlspecialchars($user->getName()),
            $resetUrl,
            $resetUrl,
        );

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html($html));
    }

    public function validateToken(string $selector, string $token): ?ResetPasswordRequest
    {
        $resetRequest = $this->resetRepo->findBySelector($selector);

        if (!$resetRequest instanceof ResetPasswordRequest || $resetRequest->isExpired()) {
            return null;
        }

        if (!hash_equals($resetRequest->getHashedToken(), hash('sha256', $token))) {
            return null;
        }

        return $resetRequest;
    }

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void
    {
        $user = $resetRequest->getUser();
        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));

        $this->entityManager->remove($resetRequest);
        $this->entityManager->flush();
    }
}
