<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslation;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Enum\CurrencyEnum;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * End-to-end functional flow for the /shop/tag/<slug> frontend route.
 *
 * Mirrors {@see ShopCategoryFunctionalTest}: tags are flat (no descendants),
 * so the seed graph is simpler — one visible tag with an attached listing and
 * one hidden tag for the 404 assertion. Inline seeding is required because
 * IntegrationTestCase only loads AppFixtures.
 */
final class ShopTagFunctionalTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    /** @var array<string, ListingTagInterface> */
    private array $tags = [];

    /** @var array<string, Listing> */
    private array $listings = [];

    private string $visibleListingTitle = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $uniq = uniqid('', false);

        $this->tags['visible'] = $this->seedTag(
            color: '#10B981',
            visible: true,
            translations: [
                'fr' => ['Nouveauté '.$uniq, 'nouveaute-'.$uniq],
                'en' => ['New '.$uniq, 'new-'.$uniq],
            ],
        );
        $this->tags['hidden'] = $this->seedTag(
            color: '#EF4444',
            visible: false,
            translations: [
                'fr' => ['Cachée '.$uniq, 'cache-tag-'.$uniq],
                'en' => ['Hidden '.$uniq, 'hidden-tag-'.$uniq],
            ],
        );

        $this->visibleListingTitle = 'Produit phare '.$uniq;
        $this->listings['visible'] = $this->seedListing('phare-'.$uniq, $this->visibleListingTitle, [$this->tags['visible']]);

        $this->entityManager->flush();
        // Clear so the request-side EM re-hydrates collections from DB rather
        // than returning empty managed collections from the persist phase.
        $this->entityManager->clear();

        $tagRepo = $this->entityManager->getRepository(ListingTag::class);
        foreach (['visible', 'hidden'] as $key) {
            $this->tags[$key] = $tagRepo->find($this->tags[$key]->getId());
        }
        $listingRepo = $this->entityManager->getRepository(Listing::class);
        foreach ($this->listings as $key => $listing) {
            $this->listings[$key] = $listingRepo->find($listing->getId());
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->listings as $listing) {
            $listing->clearTags();
            $this->entityManager->remove($listing);
            $this->entityManager->remove($listing->getProduct());
        }
        foreach (['visible', 'hidden'] as $key) {
            if (isset($this->tags[$key])) {
                $this->entityManager->remove($this->tags[$key]);
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testVisibleTagRendersListings(): void
    {
        $slug = $this->tags['visible']->getTranslation('fr')->getSlug();

        $this->client->request(HttpMethodEnum::Get->value, '/fr/shop/tag/'.$slug);

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = (string) $response->getContent();
        self::assertStringContainsString($this->visibleListingTitle, $body);
    }

    public function testHiddenTagReturnsNotFound(): void
    {
        $slug = $this->tags['hidden']->getTranslation('fr')->getSlug();

        $this->client->request(HttpMethodEnum::Get->value, '/fr/shop/tag/'.$slug);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testUnknownTagSlugReturnsNotFound(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, '/fr/shop/tag/this-tag-does-not-exist');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, array{0: string, 1: string}> $translations locale => [name, slug]
     */
    private function seedTag(string $color, bool $visible, array $translations): ListingTagInterface
    {
        $tag = new ListingTag();
        $tag->setColor($color);
        $tag->setVisible($visible);

        foreach ($translations as $locale => [$name, $slug]) {
            $translation = new ListingTagTranslation();
            $translation->setLocale($locale)
                ->setName($name)
                ->setSlug($slug)
                ->setTag($tag);
            $tag->addTranslation($translation);
            $this->entityManager->persist($translation);
        }

        $this->entityManager->persist($tag);

        return $tag;
    }

    /**
     * @param list<ListingTagInterface> $tags
     */
    private function seedListing(string $slug, string $title, array $tags): Listing
    {
        $product = new Product();
        $product->setName($title)
            ->setReference(strtoupper(substr(md5($slug), 0, 12)))
            ->setPriceCents(1000)
            ->setCurrency(CurrencyEnum::EUR)
            ->setStatus(ProductStatusEnum::Active)
            ->setType(ProductTypeEnum::Physical);
        $this->entityManager->persist($product);

        $listing = new Listing();
        $listing->setProduct($product)
            ->setSlug($slug)
            ->setMarketingTitle($title)
            ->setMarketingDescription('Demo description for '.$title)
            ->setVisibleOnShop(true);

        foreach ($tags as $tag) {
            $listing->addTag($tag);
        }

        $this->entityManager->persist($listing);

        return $listing;
    }
}
