<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Auth\Manager;

use Aurora\Core\Auth\Manager\InvitationManager;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\User\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment as TwigEnvironment;

final class InvitationManagerTest extends TestCase
{
    public function testSendInvitationSkipsWhenSelectorIsNull(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $twig = $this->createStub(TwigEnvironment::class);
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $settings = $this->createStub(SettingRepository::class);

        $manager = new InvitationManager($mailer, $twig, $urlGenerator, $settings, 'from@example.com');

        $user = new User();  // no invitation selector set

        $manager->sendInvitation($user, 'token', null);
    }

    public function testSendInvitationDispatchesEmail(): void
    {
        $user = (new User())->setEmail('user@example.com')->setName('Jane Doe');
        $user->setInvitationSelector('sel-abc');
        $user->setInvitationExpiresAt(new DateTimeImmutable('+7 days'));

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://example.com/path');

        $twig = $this->createStub(TwigEnvironment::class);
        $twig->method('render')->willReturn('<html>invitation</html>');

        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getOrDefault')->willReturn('Aurora');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $manager = new InvitationManager($mailer, $twig, $urlGenerator, $settings, 'noreply@example.com');
        $manager->sendInvitation($user, 'plain-token', 'Welcome message');
    }
}
