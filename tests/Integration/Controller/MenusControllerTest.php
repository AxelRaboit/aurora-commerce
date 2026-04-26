<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use App\Manager\MenuManager;
use App\Repository\MenuRepository;
use App\Repository\UserRepository;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class MenusControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@velox.app']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        // Clean menus
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $menuRepository = static::getContainer()->get(MenuRepository::class);
        foreach ($menuRepository->findAll() as $menu) {
            $entityManager->remove($menu);
        }
        $entityManager->flush();
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    private function postJson(string $url, array $payload = []): array
    {
        $this->client->request('POST', $url, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        return [$this->client->getResponse()->getStatusCode(), json_decode((string) $this->client->getResponse()->getContent(), true) ?? []];
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    private function getJson(string $url): array
    {
        $this->client->request('GET', $url);

        return [$this->client->getResponse()->getStatusCode(), json_decode((string) $this->client->getResponse()->getContent(), true) ?? []];
    }

    private function seedMenu(string $location = 'custom-test'): int
    {
        $menu = static::getContainer()->get(MenuManager::class)->createMenu('Test Menu', $location);

        return $menu->getId();
    }

    public function testListShowUpdateFlow(): void
    {
        $menuId = $this->seedMenu('custom-test');

        // List
        [$status, $body] = $this->getJson('/admin/menus/list');
        self::assertSame(200, $status);
        self::assertCount(1, $body['menus']);

        // Show
        [$status, $body] = $this->getJson('/admin/menus/'.$menuId);
        self::assertSame(200, $status);
        self::assertSame([], $body['menu']['items']);

        // Update name + description (location stays the same)
        [$status, $body] = $this->postJson('/admin/menus/'.$menuId.'/update', [
            'name' => 'Renamed',
            'location' => 'custom-test',
            'description' => 'Updated',
        ]);
        self::assertSame(200, $status);
        self::assertSame('Renamed', $body['menu']['name']);
        self::assertSame('Updated', $body['menu']['description']);
    }

    public function testCreateEndpointIsDisabled(): void
    {
        [$status, $body] = $this->postJson('/admin/menus/create', [
            'name' => 'Header',
            'location' => 'whatever',
        ]);
        self::assertSame(403, $status);
        self::assertFalse($body['ok']);
    }

    public function testCannotDeleteProtectedMenu(): void
    {
        $menuId = $this->seedMenu('primary');

        [$status, $body] = $this->postJson('/admin/menus/'.$menuId.'/delete');
        self::assertSame(400, $status);
        self::assertFalse($body['ok']);
    }

    public function testItemsCrud(): void
    {
        $menuId = $this->seedMenu('custom-test');

        // Create Home item
        [$status, $body] = $this->postJson('/admin/menus/'.$menuId.'/items/create', [
            'targetType' => 'home',
        ]);
        self::assertSame(200, $status);
        self::assertCount(1, $body['menu']['items']);
        $homeId = $body['menu']['items'][0]['id'];

        // Create CustomUrl item
        [$status, $body] = $this->postJson('/admin/menus/'.$menuId.'/items/create', [
            'targetType' => 'custom_url',
            'customUrl' => 'https://example.com',
            'openInNewTab' => true,
        ]);
        self::assertSame(200, $status);
        self::assertCount(2, $body['menu']['items']);
        $urlId = $body['menu']['items'][1]['id'];

        // Update item with translation
        [$status, $body] = $this->postJson('/admin/menus/items/'.$urlId.'/update', [
            'targetType' => 'custom_url',
            'customUrl' => 'https://example.org',
            'openInNewTab' => false,
            'translations' => ['fr' => 'Exemple', 'en' => 'Example'],
        ]);
        self::assertSame(200, $status);
        $updated = $body['menu']['items'][1];
        self::assertSame('https://example.org', $updated['customUrl']);
        self::assertFalse($updated['openInNewTab']);
        self::assertSame('Exemple', $updated['translations']['fr']);
        self::assertSame('Example', $updated['translations']['en']);

        // Reorder
        [$status, $body] = $this->postJson('/admin/menus/'.$menuId.'/items/reorder', [
            'items' => [
                ['id' => $urlId, 'parentId' => null, 'position' => 0],
                ['id' => $homeId, 'parentId' => null, 'position' => 1],
            ],
        ]);
        self::assertSame(200, $status);
        self::assertSame($urlId, $body['menu']['items'][0]['id']);
        self::assertSame($homeId, $body['menu']['items'][1]['id']);

        // Delete
        [$status, $body] = $this->postJson('/admin/menus/items/'.$homeId.'/delete');
        self::assertSame(200, $status);
        self::assertCount(1, $body['menu']['items']);
    }

    public function testItemValidationErrors(): void
    {
        $menuId = $this->seedMenu('custom-test');

        // Invalid target type
        [$status] = $this->postJson('/admin/menus/'.$menuId.'/items/create', ['targetType' => 'unknown']);
        self::assertSame(400, $status);

        // CustomUrl without URL
        [$status] = $this->postJson('/admin/menus/'.$menuId.'/items/create', ['targetType' => 'custom_url']);
        self::assertSame(400, $status);

        // Post without targetId
        [$status] = $this->postJson('/admin/menus/'.$menuId.'/items/create', ['targetType' => 'post']);
        self::assertSame(400, $status);
    }

    public function testPickers(): void
    {
        [$status, $body] = $this->getJson('/admin/menus/picker/post-types');
        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
        self::assertIsArray($body['items']);

        [$status, $body] = $this->getJson('/admin/menus/picker/taxonomies');
        self::assertSame(200, $status);
        self::assertTrue($body['ok']);

        [$status, $body] = $this->getJson('/admin/menus/picker/posts?q=test');
        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
        self::assertIsArray($body['items']);

        [$status, $body] = $this->getJson('/admin/menus/picker/terms?q=test');
        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
    }

    public function testIndexPageRendersWhenLogged(): void
    {
        $this->client->request('GET', '/admin/menus');
        self::assertTrue($this->client->getResponse()->isOk());
    }
}
