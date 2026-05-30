<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Frontend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Money\Enum\CurrencyEnum;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslation;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Enum\ProductStatusEnum;
use Aurora\Module\Erp\Product\Enum\ProductTypeEnum;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * End-to-end functional flow for the /shop/category/<slug> frontend route.
 *
 * Seeds a hierarchical category tree (Vêtements > Hommes / Femmes + Accessoires +
 * Caché) directly via the EM in setUp, attaches listings, then hits the route.
 *
 * IntegrationTestCase only loads AppFixtures (no Listing/Category demo data),
 * so we build the minimal graph we need inline.
 */
final class ShopCategoryFunctionalTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    /** @var array<string, ListingCategoryInterface> */
    private array $categories = [];

    /** @var array<string, Listing> */
    private array $listings = [];

    private string $menTitle = '';
    private string $womenTitle = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $uniq = uniqid('', false);
        $rootSlugFr = 'vetements-'.$uniq;
        $rootSlugEn = 'apparel-'.$uniq;
        $menSlugFr = 'hommes-'.$uniq;
        $womenSlugFr = 'femmes-'.$uniq;
        $accessoriesSlugFr = 'accessoires-'.$uniq;
        $hiddenSlugFr = 'cache-'.$uniq;

        $this->categories['root'] = $this->seedCategory(
            parent: null,
            position: 0,
            visible: true,
            translations: [
                'fr' => ['Vêtements '.$uniq, $rootSlugFr],
                'en' => ['Apparel '.$uniq, $rootSlugEn],
            ],
        );
        $this->categories['men'] = $this->seedCategory(
            parent: $this->categories['root'],
            position: 0,
            visible: true,
            translations: [
                'fr' => ['Hommes '.$uniq, $menSlugFr],
                'en' => ['Men '.$uniq, 'men-'.$uniq],
            ],
        );
        $this->categories['women'] = $this->seedCategory(
            parent: $this->categories['root'],
            position: 1,
            visible: true,
            translations: [
                'fr' => ['Femmes '.$uniq, $womenSlugFr],
                'en' => ['Women '.$uniq, 'women-'.$uniq],
            ],
        );
        $this->categories['accessories'] = $this->seedCategory(
            parent: null,
            position: 1,
            visible: true,
            translations: [
                'fr' => ['Accessoires '.$uniq, $accessoriesSlugFr],
                'en' => ['Accessories '.$uniq, 'accessories-'.$uniq],
            ],
        );
        $this->categories['hidden'] = $this->seedCategory(
            parent: null,
            position: 2,
            visible: false,
            translations: [
                'fr' => ['Cachée '.$uniq, $hiddenSlugFr],
                'en' => ['Hidden '.$uniq, 'hidden-'.$uniq],
            ],
        );

        // Listings: one in Men, one in Women, one in Accessories.
        $this->menTitle = 'T-shirt classique '.$uniq;
        $this->womenTitle = 'Robe estivale '.$uniq;
        $accessoriesTitle = 'Ceinture cuir '.$uniq;

        $this->listings['men'] = $this->seedListing('tshirt-'.$uniq, $this->menTitle, [$this->categories['men']]);
        $this->listings['women'] = $this->seedListing('robe-'.$uniq, $this->womenTitle, [$this->categories['women']]);
        $this->listings['accessories'] = $this->seedListing('ceinture-'.$uniq, $accessoriesTitle, [$this->categories['accessories']]);

        $this->entityManager->flush();
        // Clear so the request-side EM re-hydrates collections (children, listings)
        // from DB rather than returning empty managed collections from the persist phase.
        $this->entityManager->clear();

        // Re-fetch managed references for tearDown.
        $repo = $this->entityManager->getRepository(ListingCategory::class);
        foreach (['root', 'men', 'women', 'accessories', 'hidden'] as $key) {
            $this->categories[$key] = $repo->find($this->categories[$key]->getId());
        }
        $listingRepo = $this->entityManager->getRepository(Listing::class);
        foreach ($this->listings as $key => $listing) {
            $this->listings[$key] = $listingRepo->find($listing->getId());
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->listings as $listing) {
            $listing->clearCategories();
            $this->entityManager->remove($listing);
            $this->entityManager->remove($listing->getProduct());
        }
        // Remove children first to satisfy SET NULL on parent.
        foreach (['men', 'women', 'root', 'accessories', 'hidden'] as $key) {
            if (isset($this->categories[$key])) {
                $this->entityManager->remove($this->categories[$key]);
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testVisibleLeafCategoryRendersListings(): void
    {
        $slug = $this->categories['men']->getTranslation('fr')->getSlug();

        $this->client->request(HttpMethodEnum::Get->value, '/fr/shop/category/'.$slug);

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = (string) $response->getContent();
        self::assertStringContainsString($this->menTitle, $body);
    }

    public function testRootCategoryIncludesDescendantListings(): void
    {
        $rootSlug = $this->categories['root']->getTranslation('fr')->getSlug();

        $this->client->request(HttpMethodEnum::Get->value, '/fr/shop/category/'.$rootSlug);

        $response = $this->client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = (string) $response->getContent();
        self::assertStringContainsString($this->menTitle, $body, 'Root category should include Men descendant listings.');
        self::assertStringContainsString($this->womenTitle, $body, 'Root category should include Women descendant listings.');
    }

    public function testHiddenCategoryReturnsNotFound(): void
    {
        $slug = $this->categories['hidden']->getTranslation('fr')->getSlug();

        $this->client->request(HttpMethodEnum::Get->value, '/fr/shop/category/'.$slug);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, array{0: string, 1: string}> $translations locale => [name, slug]
     */
    private function seedCategory(?ListingCategoryInterface $parent, int $position, bool $visible, array $translations): ListingCategoryInterface
    {
        $category = new ListingCategory();
        $category->setParent($parent);
        $category->setPosition($position);
        $category->setVisible($visible);

        foreach ($translations as $locale => [$name, $slug]) {
            $translation = new ListingCategoryTranslation();
            $translation->setLocale($locale)
                ->setName($name)
                ->setSlug($slug)
                ->setCategory($category);
            $category->addTranslation($translation);
            $this->entityManager->persist($translation);
        }

        $this->entityManager->persist($category);

        return $category;
    }

    /**
     * @param list<ListingCategoryInterface> $categories
     */
    private function seedListing(string $slug, string $title, array $categories): Listing
    {
        $product = new Product();
        $product->setName($title)
            ->setReference(mb_strtoupper(mb_substr(md5($slug), 0, 12)))
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

        foreach ($categories as $category) {
            $listing->addCategory($category);
        }

        $this->entityManager->persist($listing);

        return $listing;
    }
}
