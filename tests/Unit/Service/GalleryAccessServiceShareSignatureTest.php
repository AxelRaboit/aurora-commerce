<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Entity\GalleryInvite;
use Aurora\Module\Photo\Gallery\Service\GalleryAccessService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class GalleryAccessServiceShareSignatureTest extends TestCase
{
    private const string SECRET = 'test-secret';

    private GalleryAccessService $service;

    protected function setUp(): void
    {
        $this->service = new GalleryAccessService(self::SECRET);
    }

    private function makeGallery(int $id = 42, ?string $passwordHash = null): Gallery
    {
        $gallery = new Gallery();
        $idProperty = new ReflectionProperty(Gallery::class, 'id');
        $idProperty->setValue($gallery, $id);
        if (null !== $passwordHash) {
            $gallery->setPasswordHash($passwordHash);
        }

        return $gallery;
    }

    public function testComputeShareSignatureIsDeterministicForSameInputs(): void
    {
        $gallery = $this->makeGallery(7, password_hash('pw', PASSWORD_BCRYPT));

        $first = $this->service->computeShareSignature($gallery, 'visitor-token');
        $second = $this->service->computeShareSignature($gallery, 'visitor-token');

        self::assertSame($first, $second);
        self::assertSame(32, mb_strlen($first));
    }

    public function testComputeShareSignatureChangesWhenPasswordRotates(): void
    {
        $gallery = $this->makeGallery(7, password_hash('first', PASSWORD_BCRYPT));
        $first = $this->service->computeShareSignature($gallery, 'visitor-token');

        $gallery->setPasswordHash(password_hash('rotated', PASSWORD_BCRYPT));
        $second = $this->service->computeShareSignature($gallery, 'visitor-token');

        self::assertNotSame($first, $second);
    }

    public function testVerifyShareSignatureReturnsTrueOnMatch(): void
    {
        $gallery = $this->makeGallery(7);
        $signature = $this->service->computeShareSignature($gallery, 'vt');

        self::assertTrue($this->service->verifyShareSignature($gallery, 'vt', $signature));
    }

    public function testVerifyShareSignatureReturnsFalseOnTamper(): void
    {
        $gallery = $this->makeGallery(7);
        $signature = $this->service->computeShareSignature($gallery, 'vt');

        self::assertFalse($this->service->verifyShareSignature($gallery, 'vt', $signature.'x'));
        self::assertFalse($this->service->verifyShareSignature($gallery, 'vt', 'deadbeef'));
        self::assertFalse($this->service->verifyShareSignature($gallery, 'other-vt', $signature));
    }

    public function testVisitorTokenForInviteTokenIsDeterministic(): void
    {
        $first = $this->service->visitorTokenForInviteToken('invite-token-abc');
        $second = $this->service->visitorTokenForInviteToken('invite-token-abc');
        $different = $this->service->visitorTokenForInviteToken('invite-token-xyz');

        self::assertSame($first, $second);
        self::assertNotSame($first, $different);
        self::assertSame(32, mb_strlen($first));
    }

    public function testUnlockForInviteCookieMatchesVisitorTokenOnInvite(): void
    {
        $gallery = $this->makeGallery(11, password_hash('pw', PASSWORD_BCRYPT));
        $visitorToken = $this->service->visitorTokenForInviteToken('inv-tok');

        $invite = (new GalleryInvite())
            ->setGallery($gallery)
            ->setName('Jane')
            ->setEmail('jane@example.com')
            ->setToken('inv-tok')
            ->setVisitorToken($visitorToken);

        $cookie = $this->service->unlockForInvite($invite);

        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame($this->service->cookieName($gallery), $cookie->getName());

        $request = new Request(cookies: [$cookie->getName() => $cookie->getValue()]);
        $readToken = $this->service->readVisitorToken($request, $gallery);

        self::assertSame($visitorToken, $readToken);
    }
}
