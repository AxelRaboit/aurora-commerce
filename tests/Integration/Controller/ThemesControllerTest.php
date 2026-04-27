<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Theme\Entity\Theme;
use Aurora\Core\Theme\Repository\ThemeRepository;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class ThemesControllerTest extends IntegrationTestCase
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

    private function defaultThemeId(): int
    {
        $theme = static::getContainer()->get(ThemeRepository::class)->findBySlug('default');
        self::assertNotNull($theme);

        return $theme->getId();
    }

    private function jsonRequest(string $method, string $url, array $payload = []): array
    {
        $this->client->request(
            $method,
            $url,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            [] !== $payload ? json_encode($payload) : '',
        );
        $response = $this->client->getResponse();

        return [$response->getStatusCode(), json_decode((string) $response->getContent(), true) ?? []];
    }

    public function testIndexReturnsOk(): void
    {
        $this->client->request('GET', '/admin/themes');

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateTheme(): void
    {
        [$status, $body] = $this->jsonRequest('POST', '/admin/themes', [
            'name' => 'Test Theme',
            'slug' => 'test-theme-create',
            'description' => '',
        ]);

        self::assertSame(200, $status, json_encode($body));
        self::assertTrue($body['ok']);
        self::assertSame('test-theme-create', $body['theme']['slug']);

        // Clean up
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $theme = static::getContainer()->get(ThemeRepository::class)->findBySlug('test-theme-create');
        if (null !== $theme) {
            $entityManager->remove($theme);
            $entityManager->flush();
        }
    }

    public function testCreateDuplicateSlugFails(): void
    {
        $slug = 'test-duplicate-slug-'.uniqid();

        $this->jsonRequest('POST', '/admin/themes', [
            'name' => 'First Theme',
            'slug' => $slug,
            'description' => '',
        ]);

        [$status, $body] = $this->jsonRequest('POST', '/admin/themes', [
            'name' => 'Second Theme',
            'slug' => $slug,
            'description' => '',
        ]);

        self::assertFalse($body['ok']);
        self::assertNotEmpty($body['errors']['slug']);

        // Clean up
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $theme = static::getContainer()->get(ThemeRepository::class)->findBySlug($slug);
        if (null !== $theme) {
            $entityManager->remove($theme);
            $entityManager->flush();
        }
    }

    public function testActivateTheme(): void
    {
        $defaultThemeId = $this->defaultThemeId();

        [$status, $body] = $this->jsonRequest('POST', sprintf('/admin/themes/%d/activate', $defaultThemeId));

        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
    }

    public function testUpdateTheme(): void
    {
        $slug = 'test-update-'.uniqid();

        [, $createBody] = $this->jsonRequest('POST', '/admin/themes', [
            'name' => 'Update Me',
            'slug' => $slug,
            'description' => '',
        ]);

        self::assertTrue($createBody['ok'], 'Create step failed: '.json_encode($createBody));
        $themeId = $createBody['theme']['id'];

        [$status, $body] = $this->jsonRequest('POST', sprintf('/admin/themes/%d/edit', $themeId), [
            'name' => 'Updated Name',
            'description' => 'new desc',
            'config' => [],
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
        self::assertSame('Updated Name', $body['theme']['name']);

        // Clean up
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $theme = $entityManager->find(Theme::class, $themeId);
        if (null !== $theme) {
            $entityManager->remove($theme);
            $entityManager->flush();
        }
    }

    public function testDeleteNonDefaultTheme(): void
    {
        $slug = 'test-delete-'.uniqid();

        [, $createBody] = $this->jsonRequest('POST', '/admin/themes', [
            'name' => 'Delete Me',
            'slug' => $slug,
            'description' => '',
        ]);

        self::assertTrue($createBody['ok'], 'Create step failed: '.json_encode($createBody));
        $themeId = $createBody['theme']['id'];

        $this->client->request('POST', sprintf('/admin/themes/%d/delete', $themeId), [], [], ['CONTENT_TYPE' => 'application/json']);
        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertTrue($body['ok']);
    }

    public function testCannotDeleteDefaultTheme(): void
    {
        $defaultThemeId = $this->defaultThemeId();

        $this->client->request('POST', sprintf('/admin/themes/%d/delete', $defaultThemeId), [], [], ['CONTENT_TYPE' => 'application/json']);
        $response = $this->client->getResponse();

        self::assertSame(400, $response->getStatusCode());
    }
}
