<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\User;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Repository\SettingRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigEnvironment;

final readonly class InvitationManager
{
    public function __construct(
        private MailerInterface $mailer,
        private TwigEnvironment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private SettingRepository $settingRepository,
        private string $mailerFrom,
    ) {}

    public function sendInvitation(User $user, string $plainToken, ?string $customMessage): void
    {
        $selector = $user->getInvitationSelector();
        if (null === $selector) {
            return;
        }

        $invitationUrl = $this->urlGenerator->generate('admin_invitation_accept', [
            'selector' => $selector,
            'token' => $plainToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $loginUrl = $this->urlGenerator->generate('admin_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $siteName = $this->settingRepository->getOrDefault(ApplicationParameterEnum::SiteName);

        $body = $this->twig->render('email/invitation.html.twig', [
            'userName' => $user->getName(),
            'customMessage' => $customMessage,
            'invitationUrl' => $invitationUrl,
            'expiresAt' => $user->getInvitationExpiresAt(),
            'loginUrl' => $loginUrl,
            'siteName' => $siteName,
        ]);

        $this->mailer->send((new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject(sprintf('Vous avez été invité à rejoindre %s', $siteName))
            ->html($body));
    }
}
