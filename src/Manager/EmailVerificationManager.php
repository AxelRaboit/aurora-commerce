<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\User;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Repository\SettingRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class EmailVerificationManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private SettingRepository $settingRepository,
        private Environment $twig,
        private TranslatorInterface $translator,
        private string $mailerFrom,
    ) {}

    /**
     * Generates a fresh verification token on the user (replacing any previous
     * one) and persists it. Returns the plain token so the caller can build
     * the verification URL.
     */
    public function generateToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationExpiresAt(new DateTimeImmutable('+24 hours'));

        $this->entityManager->flush();

        return $token;
    }

    public function sendVerificationEmail(User $user, string $verifyUrl): void
    {
        $siteName = $this->settingRepository->getOrDefault(ApplicationParameterEnum::SiteName);

        $body = $this->twig->render('email/verify_email.html.twig', [
            'userName' => $user->getName(),
            'verifyUrl' => $verifyUrl,
            'expiresAt' => $user->getEmailVerificationExpiresAt(),
            'siteName' => $siteName,
        ]);

        $subject = $this->translator->trans('mail.verify_email.heading');

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject(sprintf('[%s] %s', $siteName, $subject))
            ->html($body));
    }
}
