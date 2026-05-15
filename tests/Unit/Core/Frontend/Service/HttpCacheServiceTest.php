<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Frontend\Service;

use Aurora\Core\Frontend\Service\HttpCacheService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HttpCacheServiceTest extends TestCase
{
    public function testCheckNotModifiedReturnsNullWhenLastModifiedIsNull(): void
    {
        $service = new HttpCacheService();

        self::assertNull($service->checkNotModified(new Request(), null));
    }

    public function testCheckNotModifiedReturns304WhenClientHasFreshCopy(): void
    {
        $service = new HttpCacheService();
        $lastModified = new DateTimeImmutable('2026-01-15 10:00:00');

        $request = new Request();
        $request->headers->set('If-Modified-Since', $lastModified->format('D, d M Y H:i:s').' GMT');

        $response = $service->checkNotModified($request, $lastModified);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(304, $response->getStatusCode());
    }

    public function testCheckNotModifiedReturnsNullWhenClientHasNoCachedCopy(): void
    {
        $service = new HttpCacheService();
        $lastModified = new DateTimeImmutable('2026-01-15 10:00:00');

        $request = new Request();
        // No If-Modified-Since header

        $response = $service->checkNotModified($request, $lastModified);

        self::assertNull($response);
    }

    public function testSetPublicCacheAppliesHeaders(): void
    {
        $service = new HttpCacheService();
        $response = new Response();
        $lastModified = new DateTimeImmutable('2026-01-15 10:00:00');

        $service->setPublicCache($response, $lastModified, 600);

        self::assertNotNull($response->getLastModified());
        self::assertSame(600, $response->getMaxAge());
    }

    public function testSetPublicCacheWithNullLastModifiedSkipsHeader(): void
    {
        $service = new HttpCacheService();
        $response = new Response();

        $service->setPublicCache($response, null, 300);

        self::assertNull($response->getLastModified());
        self::assertSame(300, $response->getMaxAge());
    }

    public function testSetSharedCacheSetsSharedMaxAge(): void
    {
        $service = new HttpCacheService();
        $response = new Response();

        $service->setSharedCache($response, 120);

        self::assertTrue($response->headers->has('Cache-Control'));
    }
}
