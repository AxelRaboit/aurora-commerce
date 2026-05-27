<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Editorial\PostType\Repository\PostTypeRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PostTypesControllerTest extends IntegrationTestCase
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

    private function articlePostTypeId(): int
    {
        $pt = static::getContainer()->get(PostTypeRepository::class)->findOneBy(['slug' => 'article']);
        self::assertNotNull($pt);

        return $pt->getId();
    }

    public function testCreateCustomPostType(): void
    {
        [$status, $body] = $this->postJson('backend_editorial_post_types_create', [], [
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
        $this->client->request(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_post_types_delete', ['id' => $id]));
        self::assertSame(409, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateAndReorderFields(): void
    {
        $postTypeId = $this->articlePostTypeId();

        [, $body1] = $this->postJson('backend_editorial_post_types_field_create', ['id' => $postTypeId], [
            'name' => 'reading_time',
            'label' => 'Reading time',
            'type' => 'number',
        ]);
        self::assertTrue($body1['success']);

        [, $body2] = $this->postJson('backend_editorial_post_types_field_create', ['id' => $postTypeId], [
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

        [, $reordered] = $this->postJson('backend_editorial_post_types_field_reorder', ['id' => $postTypeId], [
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

        $this->postJson('backend_editorial_post_types_field_create', ['id' => $postTypeId], [
            'name' => 'unique_name',
            'label' => 'First',
            'type' => 'text',
        ]);

        [$status, $body] = $this->postJson('backend_editorial_post_types_field_create', ['id' => $postTypeId], [
            'name' => 'unique_name',
            'label' => 'Duplicate',
            'type' => 'text',
        ]);

        self::assertSame(422, $status);
        self::assertFalse($body['success']);
        self::assertArrayHasKey('name', $body['errors']);
    }
}
