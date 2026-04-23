<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigEnvironment;

final readonly class InvitationService
{
    public function __construct(
        private MailerInterface $mailer,
        private TwigEnvironment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private string $mailerFrom,
    ) {}

    public function send(
        string $email,
        string $message = '',
        string $credentialEmail = '',
        string $credentialPassword = '',
    ): void {
        $body = $this->twig->render('email/invitation.html.twig', [
            'customMessage' => $message ?: null,
            'credentialEmail' => $credentialEmail ?: null,
            'credentialPassword' => $credentialPassword ?: null,
            'loginUrl' => $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($email)
            ->subject('Vous avez été invité')
            ->html($body));
    }
}
