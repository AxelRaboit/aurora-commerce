<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Notes\PostIt\Entity\PostItNoteInterface;
use Aurora\Module\Notes\PostIt\Repository\PostItNoteRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PostItNotesControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;
    /** @var list<int> */
    private array $createdNoteIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->createdNoteIds = [];
    }

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $repository = static::getContainer()->get(PostItNoteRepository::class);

        foreach ($this->createdNoteIds as $id) {
            $note = $repository->find($id);
            if ($note instanceof PostItNoteInterface) {
                $entityManager->remove($note);
            }
        }
        $entityManager->flush();
        $entityManager->clear();

        parent::tearDown();
    }

    public function testIndexReturnsEmptyListForFreshUser(): void
    {
        [$status, $body] = $this->getJson('backend_notes_post_it_list');

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame([], $body['notes']);
    }

    public function testIndexPageRendersTwigTemplate(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_notes_post_it'));
        $response = $this->client->getResponse();
        $content = (string) $response->getContent();

        self::assertSame(200, $response->getStatusCode(), 'response body: '.mb_substr($content, 0, 800));
        self::assertStringContainsString('notes/backend/post_it/PostItNotesApp', $content);
    }

    public function testCreateRoundtripsFields(): void
    {
        $created = $this->createNote(
            title: 'Shopping',
            content: 'milk',
            color: '#FFCC80',
            positionX: 24,
            positionY: 24,
        );

        self::assertSame('Shopping', $created['title']);
        self::assertSame('milk', $created['content']);
        self::assertSame('#FFCC80', $created['color']);
        self::assertSame(24, $created['positionX']);
        self::assertSame(24, $created['positionY']);
        // Entity defaults — not in payload — surface back via the serializer.
        self::assertSame(220, $created['width']);
        self::assertSame(220, $created['height']);
    }

    public function testCreatedRowOnDatabaseIsEncrypted(): void
    {
        $note = $this->createNote(title: 'secret-marker-xyz', content: 'plaintext-marker-xyz');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $row = $entityManager->getConnection()
            ->fetchAssociative('SELECT title, content FROM core_post_it_notes WHERE id = :id', ['id' => $note['id']]);

        self::assertIsArray($row);
        self::assertNotSame('secret-marker-xyz', $row['title']);
        self::assertNotSame('plaintext-marker-xyz', $row['content']);
        self::assertStringNotContainsString('plaintext-marker-xyz', (string) $row['content']);
    }

    public function testUpdateChangesFields(): void
    {
        $note = $this->createNote(title: 'old');

        [$status, $body] = $this->postJson('backend_notes_post_it_update', ['id' => $note['id']], [
            'title' => 'new title',
            'content' => 'new content',
            'color' => '#A5D6A7',
        ]);

        self::assertSame(200, $status);
        self::assertSame('new title', $body['note']['title']);
        self::assertSame('new content', $body['note']['content']);
        self::assertSame('#A5D6A7', $body['note']['color']);
    }

    public function testMoveOnlyUpdatesPosition(): void
    {
        $note = $this->createNote(title: 'pinned', positionX: 0, positionY: 0);

        [$status, $body] = $this->postJson('backend_notes_post_it_move', ['id' => $note['id']], [
            'positionX' => 300,
            'positionY' => 150,
        ]);

        self::assertSame(200, $status);
        self::assertSame(300, $body['note']['positionX']);
        self::assertSame(150, $body['note']['positionY']);
        // Title preserved across the move — encrypted columns are not touched.
        self::assertSame('pinned', $body['note']['title']);
    }

    public function testResizeUpdatesWidthAndHeight(): void
    {
        $note = $this->createNote(title: 'box');

        [$status, $body] = $this->postJson('backend_notes_post_it_resize', ['id' => $note['id']], [
            'width' => 320,
            'height' => 280,
        ]);

        self::assertSame(200, $status);
        self::assertSame(320, $body['note']['width']);
        self::assertSame(280, $body['note']['height']);
    }

    public function testResizeClampsBelowMinimum(): void
    {
        $note = $this->createNote(title: 'tiny');

        [$status, $body] = $this->postJson('backend_notes_post_it_resize', ['id' => $note['id']], [
            'width' => 50,
            'height' => 30,
        ]);

        self::assertSame(200, $status);
        // Manager enforces 120×80 floor so the post-it remains usable.
        self::assertSame(120, $body['note']['width']);
        self::assertSame(80, $body['note']['height']);
    }

    public function testDeleteRemovesTheNote(): void
    {
        $note = $this->createNote(title: 'doomed');
        $id = $note['id'];

        [$status, $body] = $this->postJson('backend_notes_post_it_delete', ['id' => $id], []);
        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        $repository = static::getContainer()->get(PostItNoteRepository::class);
        self::assertNull($repository->find($id));

        // already gone — drop from teardown cleanup list
        $this->createdNoteIds = array_values(array_filter($this->createdNoteIds, fn ($i) => $i !== $id));
    }

    public function testUpdateReturnsNotFoundForUnknownId(): void
    {
        [$status, $body] = $this->postJson('backend_notes_post_it_update', ['id' => 999999], [
            'title' => 'unreachable',
        ]);

        self::assertSame(404, $status);
        self::assertFalse($body['success']);
    }

    public function testCreateRejectsInvalidColor(): void
    {
        // The DTO has a Regex assertion `^#[0-9A-Fa-f]{6}$` — anything else
        // is rejected with a validation envelope, not silently coerced.
        // 422 Unprocessable Entity is Aurora's convention for validation
        // failures (cf. PayloadValidator → jsonInvalidInput).
        [$status, $body] = $this->postJson('backend_notes_post_it_create', [], [
            'title' => 'bad-color',
            'color' => 'red',
        ]);

        self::assertSame(422, $status);
        self::assertFalse($body['success']);
    }

    /**
     * @return array<string, mixed>
     */
    private function createNote(
        string $title = 'Note',
        string $content = '',
        string $color = '#FFEB3B',
        int $positionX = 0,
        int $positionY = 0,
    ): array {
        [$status, $body] = $this->postJson('backend_notes_post_it_create', [], [
            'title' => $title,
            'content' => $content,
            'color' => $color,
            'positionX' => $positionX,
            'positionY' => $positionY,
        ]);
        self::assertSame(200, $status, 'post-it creation should succeed; got body: '.json_encode($body));

        $note = $body['note'];
        $this->createdNoteIds[] = $note['id'];

        return $note;
    }

    /**
     * @param array<string, mixed> $routeParameters
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function getJson(string $route, array $routeParameters = []): array
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate($route, $routeParameters));

        return [
            $this->client->getResponse()->getStatusCode(),
            json_decode((string) $this->client->getResponse()->getContent(), true) ?? [],
        ];
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
}
