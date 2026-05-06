<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Menu\Manager\MenuManager;
use Aurora\Core\Menu\Repository\MenuRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MenusControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'dev@aurora.app', 'type' => 'admin']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);

        // Clean menus
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $menuRepository = static::getContainer()->get(MenuRepository::class);
        foreach ($menuRepository->findAll() as $menu) {
            $entityManager->remove($menu);
        }
        $entityManager->flush();
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    private function postJson(string $route, array $routeParameters = [], array $payload = []): array
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

    /** @return array{0: int, 1: array<string, mixed>} */
    private function getJson(string $route, array $routeParameters = []): array
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate($route, $routeParameters));

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
        [$status, $body] = $this->getJson('backend_menus_list');
        self::assertSame(200, $status);
        self::assertCount(1, $body['menus']);

        // Show
        [$status, $body] = $this->getJson('backend_menus_show', ['id' => $menuId]);
        self::assertSame(200, $status);
        self::assertSame([], $body['menu']['items']);

        // Update name + description (location stays the same)
        [$status, $body] = $this->postJson('backend_menus_update', ['id' => $menuId], [
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
        [$status, $body] = $this->postJson('backend_menus_create', [], [
            'name' => 'Header',
            'location' => 'whatever',
        ]);
        self::assertSame(403, $status);
        self::assertFalse($body['success']);
    }

    public function testCannotDeleteProtectedMenu(): void
    {
        $menuId = $this->seedMenu('primary');

        [$status, $body] = $this->postJson('backend_menus_delete', ['id' => $menuId]);
        self::assertSame(400, $status);
        self::assertFalse($body['success']);
    }

    public function testItemsCrud(): void
    {
        $menuId = $this->seedMenu('custom-test');

        // Create Home item
        [$status, $body] = $this->postJson('backend_menus_items_create', ['id' => $menuId], [
            'targetType' => 'home',
        ]);
        self::assertSame(200, $status);
        self::assertCount(1, $body['menu']['items']);
        $homeId = $body['menu']['items'][0]['id'];

        // Create CustomUrl item
        [$status, $body] = $this->postJson('backend_menus_items_create', ['id' => $menuId], [
            'targetType' => 'custom_url',
            'customUrl' => 'https://example.com',
            'openInNewTab' => true,
        ]);
        self::assertSame(200, $status);
        self::assertCount(2, $body['menu']['items']);
        $urlId = $body['menu']['items'][1]['id'];

        // Update item with translation
        [$status, $body] = $this->postJson('backend_menus_items_update', ['id' => $urlId], [
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
        [$status, $body] = $this->postJson('backend_menus_items_reorder', ['id' => $menuId], [
            'items' => [
                ['id' => $urlId, 'parentId' => null, 'position' => 0],
                ['id' => $homeId, 'parentId' => null, 'position' => 1],
            ],
        ]);
        self::assertSame(200, $status);
        self::assertSame($urlId, $body['menu']['items'][0]['id']);
        self::assertSame($homeId, $body['menu']['items'][1]['id']);

        // Delete
        [$status, $body] = $this->postJson('backend_menus_items_delete', ['id' => $homeId]);
        self::assertSame(200, $status);
        self::assertCount(1, $body['menu']['items']);
    }

    public function testItemValidationErrors(): void
    {
        $menuId = $this->seedMenu('custom-test');

        // Invalid target type
        [$status] = $this->postJson('backend_menus_items_create', ['id' => $menuId], ['targetType' => 'unknown']);
        self::assertSame(400, $status);

        // CustomUrl without URL
        [$status] = $this->postJson('backend_menus_items_create', ['id' => $menuId], ['targetType' => 'custom_url']);
        self::assertSame(400, $status);

        // Post without targetId
        [$status] = $this->postJson('backend_menus_items_create', ['id' => $menuId], ['targetType' => 'post']);
        self::assertSame(400, $status);
    }

    public function testPickers(): void
    {
        [$status, $body] = $this->getJson('backend_menus_picker_post_types');
        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertIsArray($body['items']);

        [$status, $body] = $this->getJson('backend_menus_picker_taxonomies');
        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_menus_picker_posts').'?q=test');
        $status = $this->client->getResponse()->getStatusCode();
        $body = json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertIsArray($body['items']);

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_menus_picker_terms').'?q=test');
        $status = $this->client->getResponse()->getStatusCode();
        $body = json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
        self::assertSame(200, $status);
        self::assertTrue($body['success']);
    }

    public function testIndexPageRendersWhenLogged(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_menus'));
        self::assertTrue($this->client->getResponse()->isOk());
    }
}
