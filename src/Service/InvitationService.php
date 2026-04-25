<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
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
        private string $siteName = 'Velox',
    ) {}

    public function sendInvitation(User $user, string $plainToken, ?string $customMessage): void
    {
        $selector = $user->getInvitationSelector();
        if (null === $selector) {
            return;
        }

        $invitationUrl = $this->urlGenerator->generate('app_invitation_accept', [
            'selector' => $selector,
            'token' => $plainToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $loginUrl = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $body = $this->twig->render('email/invitation.html.twig', [
            'userName' => $user->getName(),
            'customMessage' => $customMessage,
            'invitationUrl' => $invitationUrl,
            'expiresAt' => $user->getInvitationExpiresAt(),
            'loginUrl' => $loginUrl,
            'siteName' => $this->siteName,
        ]);

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject(sprintf('Vous avez été invité à rejoindre %s', $this->siteName))
            ->html($body));
    }
}
