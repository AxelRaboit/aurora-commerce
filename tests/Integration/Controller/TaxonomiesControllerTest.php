<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class TaxonomiesControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
    }

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

        return [$this->client->getResponse()->getStatusCode(), json_decode((string) $this->client->getResponse()->getContent(), true) ?? []];
    }

    private function categoryTaxonomyId(): int
    {
        $category = static::getContainer()->get(TaxonomyRepository::class)->findOneBySlug('category');
        self::assertNotNull($category);

        return $category->getId();
    }

    public function testCreateCustomTaxonomy(): void
    {
        [$status, $body] = $this->postJson('backend_taxonomies_create', [], [
            'slug' => 'genre',
            'hierarchical' => false,
            'translations' => [
                'fr' => ['label' => 'Genre', 'description' => null],
                'en' => ['label' => 'Genre', 'description' => null],
            ],
            'postTypeIds' => [],
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertFalse($body['taxonomy']['isBuiltIn']);
        self::assertSame('genre', $body['taxonomy']['slug']);
    }

    public function testCannotDeleteBuiltInTaxonomy(): void
    {
        $taxonomyId = $this->categoryTaxonomyId();
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_taxonomies_delete', ['id' => $taxonomyId]));
        self::assertSame(409, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateAndReorderTermsWithCycleRejection(): void
    {
        $taxonomyId = $this->categoryTaxonomyId();

        [$status1, $body1] = $this->postJson('backend_taxonomies_term_create', ['id' => $taxonomyId], [
            'translations' => ['fr' => ['name' => 'Parent', 'slug' => 'parent'], 'en' => ['name' => 'Parent', 'slug' => 'parent-en']],
        ]);
        self::assertTrue($body1['success']);
        $parentId = $body1['termId'];

        [$status2, $body2] = $this->postJson('backend_taxonomies_term_create', ['id' => $taxonomyId], [
            'parentId' => $parentId,
            'translations' => ['fr' => ['name' => 'Enfant', 'slug' => 'enfant'], 'en' => ['name' => 'Child', 'slug' => 'child']],
        ]);
        self::assertTrue($body2['success']);
        $childId = $body2['termId'];

        // Both terms pointing at each other creates a true cycle → must fail
        [$statusCycle, $bodyCycle] = $this->postJson('backend_taxonomies_term_reorder', ['id' => $taxonomyId], [
            'entries' => [
                ['id' => $parentId, 'parentId' => $childId, 'position' => 0],
                ['id' => $childId, 'parentId' => $parentId, 'position' => 0],
            ],
        ]);
        self::assertSame(400, $statusCycle);
        self::assertFalse($bodyCycle['success']);

        // Valid reorder at root level
        [$statusOk, $bodyOk] = $this->postJson('backend_taxonomies_term_reorder', ['id' => $taxonomyId], [
            'entries' => [
                ['id' => $parentId, 'parentId' => null, 'position' => 0],
                ['id' => $childId, 'parentId' => $parentId, 'position' => 0],
            ],
        ]);
        self::assertSame(200, $statusOk);
        self::assertTrue($bodyOk['success']);
    }

    public function testDeletingTermPromotesChildrenToParent(): void
    {
        $taxonomyId = $this->categoryTaxonomyId();

        [, $grandparentBody] = $this->postJson('backend_taxonomies_term_create', ['id' => $taxonomyId], [
            'translations' => ['fr' => ['name' => 'Grand-parent', 'slug' => 'grand-parent'], 'en' => ['name' => 'Grandparent', 'slug' => 'grandparent']],
        ]);
        $grandparentId = $grandparentBody['termId'];

        [, $parentBody] = $this->postJson('backend_taxonomies_term_create', ['id' => $taxonomyId], [
            'parentId' => $grandparentId,
            'translations' => ['fr' => ['name' => 'Parent', 'slug' => 'parent-d'], 'en' => ['name' => 'Parent', 'slug' => 'parent-d-en']],
        ]);
        $parentId = $parentBody['termId'];

        [, $childBody] = $this->postJson('backend_taxonomies_term_create', ['id' => $taxonomyId], [
            'parentId' => $parentId,
            'translations' => ['fr' => ['name' => 'Enfant', 'slug' => 'enfant-d'], 'en' => ['name' => 'Child', 'slug' => 'child-d']],
        ]);
        $childId = $childBody['termId'];

        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_taxonomies_term_delete', ['id' => $taxonomyId, 'termId' => $parentId]));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $terms = static::getContainer()->get(TaxonomyTermRepository::class);
        $child = $terms->find($childId);
        self::assertNotNull($child);
        self::assertSame($grandparentId, $child->getParent()?->getId());
    }
}
