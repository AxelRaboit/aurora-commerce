<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Repository\ListingCategoryRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ListingCategoriesControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;
    /** @var list<int> */
    private array $createdCategoryIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->createdCategoryIds = [];
    }

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $repository = static::getContainer()->get(ListingCategoryRepository::class);

        // Remove deepest-first so parent FK constraints are not violated.
        $categories = [];
        foreach ($this->createdCategoryIds as $id) {
            $category = $repository->find($id);
            if ($category instanceof ListingCategoryInterface) {
                $categories[] = $category;
            }
        }
        usort($categories, fn ($a, $b): int => $b->getDepth() <=> $a->getDepth());
        foreach ($categories as $category) {
            $entityManager->remove($category);
        }
        $entityManager->flush();
        $entityManager->clear();

        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $routeParameters
     * @param array<string, mixed> $payload
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function postJson(string $route, array $routeParameters, array $payload): array
    {
        $this->client->request(
            HttpMethodEnum::Post->value,
            $this->urlGenerator->generate($route, $routeParameters),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        return [
            $this->client->getResponse()->getStatusCode(),
            json_decode((string) $this->client->getResponse()->getContent(), true) ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createCategory(?int $parentId = null, string $name = 'Cat'): array
    {
        [$status, $body] = $this->postJson('backend_ecommerce_listing_categories_create', [], [
            'parentId' => $parentId,
            'position' => 0,
            'isVisible' => true,
            'imageId' => null,
            'translations' => [
                'en' => ['name' => $name, 'slug' => mb_strtolower($name).'-'.bin2hex(random_bytes(3)), 'description' => null, 'seoTitle' => null, 'seoDescription' => null],
            ],
        ]);
        self::assertSame(200, $status, json_encode($body));
        self::assertTrue($body['success'], json_encode($body));

        $this->createdCategoryIds[] = (int) $body['category']['id'];

        return $body['category'];
    }

    public function testReorderUpdatesParentAndPosition(): void
    {
        $alpha = $this->createCategory(null, 'Alpha');
        $beta = $this->createCategory(null, 'Beta');
        $gamma = $this->createCategory(null, 'Gamma');

        // Move gamma under alpha, swap alpha/beta order at root.
        [$status, $body] = $this->postJson('backend_ecommerce_listing_categories_reorder', [], [
            'entries' => [
                ['id' => $beta['id'], 'parentId' => null, 'position' => 0],
                ['id' => $alpha['id'], 'parentId' => null, 'position' => 1],
                ['id' => $gamma['id'], 'parentId' => $alpha['id'], 'position' => 0],
            ],
        ]);
        self::assertSame(200, $status, json_encode($body));
        self::assertTrue($body['success']);
        self::assertIsArray($body['items']);

        $repository = static::getContainer()->get(ListingCategoryRepository::class);
        static::getContainer()->get(EntityManagerInterface::class)->clear();

        $reloadedBeta = $repository->find($beta['id']);
        $reloadedAlpha = $repository->find($alpha['id']);
        $reloadedGamma = $repository->find($gamma['id']);

        self::assertNotNull($reloadedAlpha);
        self::assertNotNull($reloadedBeta);
        self::assertNotNull($reloadedGamma);

        self::assertNull($reloadedBeta->getParent());
        self::assertSame(0, $reloadedBeta->getPosition());

        self::assertNull($reloadedAlpha->getParent());
        self::assertSame(1, $reloadedAlpha->getPosition());

        self::assertSame($alpha['id'], $reloadedGamma->getParent()?->getId());
        self::assertSame(0, $reloadedGamma->getPosition());
    }

    public function testReorderRejectsCycle(): void
    {
        $first = $this->createCategory(null, 'CycleA');
        $second = $this->createCategory($first['id'], 'CycleB');

        [$status, $body] = $this->postJson('backend_ecommerce_listing_categories_reorder', [], [
            'entries' => [
                ['id' => $first['id'], 'parentId' => $second['id'], 'position' => 0],
                ['id' => $second['id'], 'parentId' => $first['id'], 'position' => 0],
            ],
        ]);

        self::assertSame(400, $status);
        self::assertFalse($body['success']);
    }
}
