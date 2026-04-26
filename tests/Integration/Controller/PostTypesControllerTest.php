<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Core\User\Entity\User;
use App\Core\User\Repository\UserRepository;
use App\Module\Editorial\Post\Repository\PostTypeRepository;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class PostTypesControllerTest extends IntegrationTestCase
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

    private function articlePostTypeId(): int
    {
        $pt = static::getContainer()->get(PostTypeRepository::class)->findOneBy(['slug' => 'article']);
        self::assertNotNull($pt);

        return $pt->getId();
    }

    public function testCreateCustomPostType(): void
    {
        [$status, $body] = $this->postJson('/admin/post-types', [
            'slug' => 'recipe',
            'label' => 'Recipes',
            'icon' => 'utensils',
            'hasArchive' => true,
            'supports' => ['blocks', 'thumbnail'],
            'taxonomyIds' => [],
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertFalse($body['postType']['isBuiltIn']);
        self::assertSame('recipe', $body['postType']['slug']);
    }

    public function testCannotDeleteBuiltInPostType(): void
    {
        $id = $this->articlePostTypeId();
        $this->client->request('POST', sprintf('/admin/post-types/%d/delete', $id));
        self::assertSame(409, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateAndReorderFields(): void
    {
        $postTypeId = $this->articlePostTypeId();

        [, $body1] = $this->postJson(sprintf('/admin/post-types/%d/fields', $postTypeId), [
            'name' => 'reading_time',
            'label' => 'Reading time',
            'type' => 'number',
        ]);
        self::assertTrue($body1['success']);

        [, $body2] = $this->postJson(sprintf('/admin/post-types/%d/fields', $postTypeId), [
            'name' => 'priority',
            'label' => 'Priority',
            'type' => 'select',
            'options' => ['choices' => [['value' => 'low', 'label' => 'Low'], ['value' => 'high', 'label' => 'High']]],
        ]);
        self::assertTrue($body2['success']);

        $fields = $body2['postType']['fields'];
        self::assertCount(2, $fields);
        $readingTimeField = array_values(array_filter($fields, static fn ($f) => 'reading_time' === $f['name']))[0];
        $priorityField = array_values(array_filter($fields, static fn ($f) => 'priority' === $f['name']))[0];

        [, $reordered] = $this->postJson(sprintf('/admin/post-types/%d/fields/reorder', $postTypeId), [
            'orderedIds' => [$priorityField['id'], $readingTimeField['id']],
        ]);
        self::assertTrue($reordered['success']);
        $positions = [];
        foreach ($reordered['postType']['fields'] as $field) {
            $positions[$field['name']] = $field['position'];
        }
        self::assertLessThan($positions['reading_time'], $positions['priority']);
    }

    public function testDuplicateFieldNameRejected(): void
    {
        $postTypeId = $this->articlePostTypeId();

        $this->postJson(sprintf('/admin/post-types/%d/fields', $postTypeId), [
            'name' => 'unique_name',
            'label' => 'First',
            'type' => 'text',
        ]);

        [$status, $body] = $this->postJson(sprintf('/admin/post-types/%d/fields', $postTypeId), [
            'name' => 'unique_name',
            'label' => 'Duplicate',
            'type' => 'text',
        ]);

        self::assertSame(200, $status);
        self::assertFalse($body['success']);
        self::assertArrayHasKey('name', $body['errors']);
    }
}
