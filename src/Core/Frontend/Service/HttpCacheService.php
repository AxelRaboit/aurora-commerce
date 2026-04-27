<?php

declare(strict_types=1);

namespace Aurora\Core\Frontend\Service;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HttpCacheService
{
    /**
     * Checks if the client cache is still fresh.
     * Returns a 304 response if fresh, null otherwise.
     * Use this BEFORE doing expensive rendering to short-circuit the request.
     */
    public function checkNotModified(Request $request, ?DateTimeInterface $lastModified, int $maxAge = 300): ?Response
    {
        if (!$lastModified instanceof DateTimeInterface) {
            return null;
        }

        $response = new Response();
        $response->setLastModified($lastModified);
        $response->setPublic();
        $response->setMaxAge($maxAge);

        if ($response->isNotModified($request)) {
            return $response;
        }

        return null;
    }

    /**
     * Applies public cache headers to an existing response.
     */
    public function setPublicCache(Response $response, ?DateTimeInterface $lastModified, int $maxAge = 300): void
    {
        if ($lastModified instanceof DateTimeInterface) {
            $response->setLastModified($lastModified);
        }

        $response->setPublic();
        $response->setMaxAge($maxAge);
    }

    /**
     * Applies a short shared cache (CDN/proxy) without Last-Modified.
     * Suitable for list pages whose content changes more frequently.
     */
    public function setSharedCache(Response $response, int $sharedMaxAge = 60): void
    {
        $response->setPublic();
        $response->setSharedMaxAge($sharedMaxAge);
    }
}
