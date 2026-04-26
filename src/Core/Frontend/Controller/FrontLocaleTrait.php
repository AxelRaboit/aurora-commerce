<?php

declare(strict_types=1);

namespace App\Core\Frontend\Controller;

use App\Core\Frontend\Service\FrontContext;
use Symfony\Component\HttpFoundation\Response;

trait FrontLocaleTrait
{
    private function assertActiveLocale(FrontContext $frontContext, string $locale): void
    {
        if (!$frontContext->isLocaleActive($locale)) {
            throw $this->createNotFoundException(sprintf('Locale "%s" is not active.', $locale));
        }
    }

    private function withI18nHeaders(Response $response, string $locale): Response
    {
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
