<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryInvite;
use Aurora\Module\Photo\Gallery\Repository\GalleryPickRepository;
use Aurora\Module\Photo\Gallery\Service\GalleryAccessService;
use Aurora\Module\Photo\Gallery\Service\GalleryInviteManager;
use Aurora\Module\Photo\Gallery\Service\GalleryNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Notes on the testing approach:
 *
 * GalleryNotificationService and GalleryAccessService are both `final readonly`,
 * which PHPUnit cannot subclass into mocks. Instead we wire real instances with
 * mocked low-level collaborators (mailer, twig, settings, pick repo) and observe
 * the mailer for the side-effect we care about — that the invite notification
 * was sent with the magic URL embedded.
 */
final class GalleryInviteManagerTest extends TestCase
{
    private function makeGallery(int $id = 1, string $slug = 'wedding-2026'): Gallery
    {
        $gallery = new Gallery();
        $idProperty = new ReflectionProperty(Gallery::class, 'id');
        $idProperty->setValue($gallery, $id);
        $gallery->setSlug($slug);
        $gallery->setTitle('Wedding 2026');

        return $gallery;
    }

    private function makeNotificationService(MailerInterface $mailer, UrlGeneratorInterface $url): GalleryNotificationService
    {
        $twig = new Environment(new ArrayLoader([
            'email/gallery_invite.html.twig' => '<a href="{{ magicUrl }}">link</a>',
            '@Photo/email/gallery_invite.html.twig' => '<a href="{{ magicUrl }}">link</a>',
        ]));

        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getOrDefault')->willReturn('Aurora');

        $pickRepo = $this->createStub(GalleryPickRepository::class);

        return new GalleryNotificationService(
            $mailer,
            $twig,
            $settings,
            $pickRepo,
            $url,
            'noreply@example.test',
        );
    }

    public function testCreatePersistsInviteWithLowercasedEmailAndDeterministicVisitorToken(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $mailer = $this->createStub(MailerInterface::class);
        $url = $this->createStub(UrlGeneratorInterface::class);
        $notifier = $this->makeNotificationService($mailer, $url);
        $access = new GalleryAccessService('test-secret');

        $em->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(GalleryInvite::class));
        $em->expects(self::once())->method('flush');

        $manager = new GalleryInviteManager($em, $notifier, $url, $access);
        $gallery = $this->makeGallery();

        $invite = $manager->create($gallery, 'Jane Doe', 'JANE@Example.COM');

        self::assertSame('Jane Doe', $invite->getName());
        self::assertSame('jane@example.com', $invite->getEmail());
        self::assertSame(48, mb_strlen($invite->getToken()));
        self::assertSame($gallery, $invite->getGallery());

        // Visitor token must be exactly what GalleryAccessService derives from the invite token.
        self::assertSame(
            $access->visitorTokenForInviteToken($invite->getToken()),
            $invite->getVisitorToken(),
        );
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $mailer = $this->createStub(MailerInterface::class);
        $url = $this->createStub(UrlGeneratorInterface::class);
        $notifier = $this->makeNotificationService($mailer, $url);
        $access = new GalleryAccessService('test-secret');

        $invite = new GalleryInvite();
        $em->expects(self::once())->method('remove')->with($invite);
        $em->expects(self::once())->method('flush');

        $manager = new GalleryInviteManager($em, $notifier, $url, $access);
        $manager->delete($invite);
    }

    public function testSendCallsNotifierAndMarksSent(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $mailer = $this->createMock(MailerInterface::class);
        $url = $this->createMock(UrlGeneratorInterface::class);
        $notifier = $this->makeNotificationService($mailer, $url);
        $access = new GalleryAccessService('test-secret');

        $gallery = $this->makeGallery(1, 'wedding-2026');
        $invite = (new GalleryInvite())
            ->setGallery($gallery)
            ->setName('Jane')
            ->setEmail('jane@example.com')
            ->setToken('tok-abc')
            ->setVisitorToken('vt');

        $expectedUrl = 'https://example.test/g/wedding-2026/invite/tok-abc';
        $url->expects(self::once())
            ->method('generate')
            ->with('front_gallery_invite_redeem', ['slug' => 'wedding-2026', 'token' => 'tok-abc'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($expectedUrl);

        $captured = null;
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (RawMessage $message) use (&$captured): bool {
                $captured = $message;

                return true;
            }));

        $em->expects(self::once())->method('flush');

        $manager = new GalleryInviteManager($em, $notifier, $url, $access);

        self::assertNull($invite->getSentAt());
        $manager->send($invite);
        self::assertNotNull($invite->getSentAt());

        self::assertInstanceOf(Email::class, $captured);
        self::assertStringContainsString($expectedUrl, $captured->getHtmlBody() ?? '');
        $tos = array_map(static fn ($a) => $a->getAddress(), $captured->getTo());
        self::assertContains('jane@example.com', $tos);
    }

    public function testMarkSeenSetsLastSeenAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $mailer = $this->createStub(MailerInterface::class);
        $url = $this->createStub(UrlGeneratorInterface::class);
        $notifier = $this->makeNotificationService($mailer, $url);
        $access = new GalleryAccessService('test-secret');

        $invite = new GalleryInvite();
        self::assertNull($invite->getLastSeenAt());

        $em->expects(self::once())->method('flush');

        $manager = new GalleryInviteManager($em, $notifier, $url, $access);
        $manager->markSeen($invite);

        self::assertNotNull($invite->getLastSeenAt());
    }
}
