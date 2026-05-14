<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ShopCategoryControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
    }

    public function testCategoryRouteIsRegistered(): void
    {
        $url = $this->urlGenerator->generate('frontend_shop_category', ['locale' => 'fr', 'slug' => 'no-such-category']);
        self::assertSame('/fr/shop/category/no-such-category', $url);
    }

    public function testUnknownCategorySlugReturnsNotFound(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, '/fr/shop/category/this-category-does-not-exist');
        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }
}
