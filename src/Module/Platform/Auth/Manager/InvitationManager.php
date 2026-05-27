<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\Auth\Manager;

use Aurora\Module\Configuration\Setting\Enum\ApplicationParameterEnum;
use Aurora\Module\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Platform\User\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigEnvironment;

#[AsAlias(InvitationManagerInterface::class)]
class InvitationManager implements InvitationManagerInterface
{
    public function __construct(
        protected readonly MailerInterface $mailer,
        protected readonly TwigEnvironment $twig,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly SettingRepository $settingRepository,
        protected readonly string $mailerFrom,
    ) {}

    public function sendInvitation(User $user, string $plainToken, ?string $customMessage): void
    {
        $selector = $user->getInvitationSelector();
        if (null === $selector) {
            return;
        }

        $invitationUrl = $this->urlGenerator->generate('backend_platform_invitation_accept', [
            'selector' => $selector,
            'token' => $plainToken,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $loginUrl = $this->urlGenerator->generate('backend_platform_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $siteName = $this->settingRepository->getOrDefault(ApplicationParameterEnum::SiteName);

        $body = $this->twig->render('@Shared/email/invitation.html.twig', [
            'userName' => $user->getName(),
            'customMessage' => $customMessage,
            'invitationUrl' => $invitationUrl,
            'expiresAt' => $user->getInvitationExpiresAt(),
            'loginUrl' => $loginUrl,
            'siteName' => $siteName,
        ]);

        $this->mailer->send(new Email()
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject(sprintf('Vous avez été invité à rejoindre %s', $siteName))
            ->html($body));
    }
}
