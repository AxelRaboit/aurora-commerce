<?php

declare(strict_types=1);

namespace Aurora\Core\Platform\Auth\Manager;

use Aurora\Core\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[AsAlias(EmailVerificationManagerInterface::class)]
class EmailVerificationManager implements EmailVerificationManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly MailerInterface $mailer,
        protected readonly SettingRepository $settingRepository,
        protected readonly Environment $twig,
        protected readonly TranslatorInterface $translator,
        protected readonly string $mailerFrom,
    ) {}

    /**
     * Generates a fresh verification token on the user (replacing any previous
     * one) and persists it. Returns the plain token so the caller can build
     * the verification URL.
     */
    public function generateToken(CoreUserInterface $user): string
    {
        $token = bin2hex(random_bytes(32));
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationExpiresAt(new DateTimeImmutable('+24 hours'));

        $this->entityManager->flush();

        return $token;
    }

    public function sendVerificationEmail(CoreUserInterface $user, string $verifyUrl): void
    {
        $siteName = $this->settingRepository->getOrDefault(ApplicationParameterEnum::SiteName);

        $body = $this->twig->render('@Shared/email/verify_email.html.twig', [
            'userName' => $user->getName(),
            'verifyUrl' => $verifyUrl,
            'expiresAt' => $user->getEmailVerificationExpiresAt(),
            'siteName' => $siteName,
        ]);

        $subject = $this->translator->trans('shared.mail.verify_email.heading');

        $this->mailer->send(new Email()
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject(sprintf('[%s] %s', $siteName, $subject))
            ->html($body));
    }
}
