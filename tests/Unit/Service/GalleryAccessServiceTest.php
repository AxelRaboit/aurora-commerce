<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Service;

use Aurora\Module\Photo\Gallery\Entity\Gallery;
use Aurora\Module\Photo\Gallery\Service\GalleryAccessService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class GalleryAccessServiceTest extends TestCase
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

    public function testCookieNameIncludesGalleryId(): void
    {
        $gallery = $this->makeGallery(7);

        self::assertSame('aurora_gallery_7', $this->service->cookieName($gallery));
    }

    public function testUnlockReturnsNullWhenPasswordRequiredButMissing(): void
    {
        $gallery = $this->makeGallery(1, password_hash('secret', PASSWORD_BCRYPT));

        self::assertNull($this->service->unlock($gallery, null));
        self::assertNull($this->service->unlock($gallery, ''));
    }

    public function testUnlockReturnsNullOnWrongPassword(): void
    {
        $gallery = $this->makeGallery(1, password_hash('secret', PASSWORD_BCRYPT));

        self::assertNull($this->service->unlock($gallery, 'wrong'));
    }

    public function testUnlockIssuesCookieWithCorrectPassword(): void
    {
        $gallery = $this->makeGallery(1, password_hash('secret', PASSWORD_BCRYPT));

        $cookie = $this->service->unlock($gallery, 'secret');

        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertSame('aurora_gallery_1', $cookie->getName());
        self::assertStringContainsString('|', (string) $cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
    }

    public function testUnlockNoPasswordIssuesCookieTransparently(): void
    {
        $gallery = $this->makeGallery(1, null);

        $cookie = $this->service->unlock($gallery, null);

        self::assertInstanceOf(Cookie::class, $cookie);
    }

    public function testReadVisitorTokenReturnsNullWhenCookieMissing(): void
    {
        $gallery = $this->makeGallery(1);
        $request = new Request();

        self::assertNull($this->service->readVisitorToken($request, $gallery));
    }

    public function testReadVisitorTokenReturnsTokenForValidCookie(): void
    {
        $gallery = $this->makeGallery(1);
        $cookie = $this->service->unlock($gallery, null);
        self::assertInstanceOf(Cookie::class, $cookie);

        $request = new Request(cookies: [$cookie->getName() => $cookie->getValue()]);

        $token = $this->service->readVisitorToken($request, $gallery);
        self::assertNotNull($token);
        self::assertSame(32, mb_strlen($token));
    }

    public function testReadVisitorTokenRejectsTamperedHmac(): void
    {
        $gallery = $this->makeGallery(1);
        $cookie = $this->service->unlock($gallery, null);
        self::assertInstanceOf(Cookie::class, $cookie);

        $tampered = explode('|', (string) $cookie->getValue(), 2)[0].'|deadbeef';
        $request = new Request(cookies: [$cookie->getName() => $tampered]);

        self::assertNull($this->service->readVisitorToken($request, $gallery));
    }

    public function testReadVisitorTokenRejectsMalformedCookie(): void
    {
        $gallery = $this->makeGallery(1);
        $request = new Request(cookies: [$this->service->cookieName($gallery) => 'no-pipe-here']);

        self::assertNull($this->service->readVisitorToken($request, $gallery));
    }

    public function testPasswordRotationInvalidatesExistingCookie(): void
    {
        $gallery = $this->makeGallery(1, password_hash('first', PASSWORD_BCRYPT));
        $cookie = $this->service->unlock($gallery, 'first');
        self::assertInstanceOf(Cookie::class, $cookie);

        $gallery->setPasswordHash(password_hash('rotated', PASSWORD_BCRYPT));
        $request = new Request(cookies: [$cookie->getName() => $cookie->getValue()]);

        self::assertNull($this->service->readVisitorToken($request, $gallery));
    }
}
