<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyRepository;
use Aurora\Module\Editorial\Taxonomy\Repository\TaxonomyTermRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class TaxonomiesControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'admin@aurora.app']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');
    }

    private function postJson(string $url, array $payload): array
    {
        $this->client->request('POST', $url, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

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
        [$status, $body] = $this->postJson('/admin/taxonomies', [
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
        $this->client->request('POST', sprintf('/admin/taxonomies/%d/delete', $taxonomyId));
        self::assertSame(409, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateAndReorderTermsWithCycleRejection(): void
    {
        $taxonomyId = $this->categoryTaxonomyId();

        [$status1, $body1] = $this->postJson(sprintf('/admin/taxonomies/%d/terms', $taxonomyId), [
            'translations' => ['fr' => ['name' => 'Parent', 'slug' => 'parent'], 'en' => ['name' => 'Parent', 'slug' => 'parent-en']],
        ]);
        self::assertTrue($body1['success']);
        $parentId = $body1['termId'];

        [$status2, $body2] = $this->postJson(sprintf('/admin/taxonomies/%d/terms', $taxonomyId), [
            'parentId' => $parentId,
            'translations' => ['fr' => ['name' => 'Enfant', 'slug' => 'enfant'], 'en' => ['name' => 'Child', 'slug' => 'child']],
        ]);
        self::assertTrue($body2['success']);
        $childId = $body2['termId'];

        // Both terms pointing at each other creates a true cycle → must fail
        [$statusCycle, $bodyCycle] = $this->postJson(sprintf('/admin/taxonomies/%d/terms/reorder', $taxonomyId), [
            'entries' => [
                ['id' => $parentId, 'parentId' => $childId, 'position' => 0],
                ['id' => $childId, 'parentId' => $parentId, 'position' => 0],
            ],
        ]);
        self::assertSame(400, $statusCycle);
        self::assertFalse($bodyCycle['success']);

        // Valid reorder at root level
        [$statusOk, $bodyOk] = $this->postJson(sprintf('/admin/taxonomies/%d/terms/reorder', $taxonomyId), [
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

        [, $grandparentBody] = $this->postJson(sprintf('/admin/taxonomies/%d/terms', $taxonomyId), [
            'translations' => ['fr' => ['name' => 'Grand-parent', 'slug' => 'grand-parent'], 'en' => ['name' => 'Grandparent', 'slug' => 'grandparent']],
        ]);
        $grandparentId = $grandparentBody['termId'];

        [, $parentBody] = $this->postJson(sprintf('/admin/taxonomies/%d/terms', $taxonomyId), [
            'parentId' => $grandparentId,
            'translations' => ['fr' => ['name' => 'Parent', 'slug' => 'parent-d'], 'en' => ['name' => 'Parent', 'slug' => 'parent-d-en']],
        ]);
        $parentId = $parentBody['termId'];

        [, $childBody] = $this->postJson(sprintf('/admin/taxonomies/%d/terms', $taxonomyId), [
            'parentId' => $parentId,
            'translations' => ['fr' => ['name' => 'Enfant', 'slug' => 'enfant-d'], 'en' => ['name' => 'Child', 'slug' => 'child-d']],
        ]);
        $childId = $childBody['termId'];

        $this->client->request('POST', sprintf('/admin/taxonomies/%d/terms/%d/delete', $taxonomyId, $parentId));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $terms = static::getContainer()->get(TaxonomyTermRepository::class);
        $child = $terms->find($childId);
        self::assertNotNull($child);
        self::assertSame($grandparentId, $child->getParent()?->getId());
    }
}
