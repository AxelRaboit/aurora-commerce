<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Notes\Block\Entity\BlockNoteInterface;
use Aurora\Module\Notes\Block\Repository\BlockNoteRepository;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BlockNotesControllerTest extends IntegrationTestCase
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
        $repository = static::getContainer()->get(BlockNoteRepository::class);

        foreach ($this->createdNoteIds as $id) {
            $note = $repository->find($id);
            if ($note instanceof BlockNoteInterface) {
                $entityManager->remove($note);
            }
        }
        $entityManager->flush();
        $entityManager->clear();

        parent::tearDown();
    }

    public function testIndexReturnsEmptyListForFreshUser(): void
    {
        [$status, $body] = $this->getJson('backend_notes_block_list');

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame([], $body['notes']);
    }

    public function testIndexPageRendersTwigTemplate(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_notes_block'));
        $response = $this->client->getResponse();
        $content = (string) $response->getContent();

        self::assertSame(200, $response->getStatusCode(), 'response body: '.mb_substr($content, 0, 800));
        self::assertStringContainsString('notes/backend/block/BlockNotesApp', $content);
    }

    public function testCreateThenShowRoundtripsBlocks(): void
    {
        $blocks = [
            ['id' => 'h1', 'type' => 'header', 'data' => ['text' => 'Title', 'level' => 2]],
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Body']],
        ];
        $created = $this->createNote(title: 'My note', tags: ['draft'], blocks: $blocks);

        self::assertSame('My note', $created['title']);
        self::assertSame(['draft'], $created['tags']);
        self::assertSame(0, $created['position']);
        self::assertNull($created['parentId']);
        self::assertCount(2, $created['blocks']);

        [$status, $body] = $this->getJson('backend_notes_block_show', ['id' => $created['id']]);
        self::assertSame(200, $status);
        self::assertCount(2, $body['note']['blocks']);
        self::assertSame('header', $body['note']['blocks'][0]['type']);
        self::assertSame(['text' => 'Title', 'level' => 2], $body['note']['blocks'][0]['data']);
    }

    public function testCreatedRowOnDatabaseEncryptsTitleButNotJsonBlocks(): void
    {
        // Title is encrypted at rest via EncryptedTextType; the `blocks`
        // JSON column is NOT encrypted (search runs SQL on it).
        $note = $this->createNote(title: 'secret-marker-xyz');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $row = $entityManager->getConnection()
            ->fetchAssociative('SELECT title FROM core_block_notes WHERE id = :id', ['id' => $note['id']]);

        self::assertIsArray($row);
        self::assertNotSame('secret-marker-xyz', $row['title']);
    }

    public function testUpdateChangesFields(): void
    {
        $note = $this->createNote(title: 'old');

        [$status, $body] = $this->postJson('backend_notes_block_update', ['id' => $note['id']], [
            'title' => 'new',
            'tags' => ['urgent', 'work'],
            'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'updated body']],
            ],
        ]);

        self::assertSame(200, $status);
        self::assertSame('new', $body['note']['title']);
        self::assertSame(['urgent', 'work'], $body['note']['tags']);
        self::assertCount(1, $body['note']['blocks']);
        self::assertSame(['text' => 'updated body'], $body['note']['blocks'][0]['data']);
    }

    public function testUpdateClearsBlocksWhenEmptyArrayIsSent(): void
    {
        $note = $this->createNote(blocks: [
            ['type' => 'paragraph', 'data' => ['text' => 'remove me']],
        ]);

        [$status, $body] = $this->postJson('backend_notes_block_update', ['id' => $note['id']], [
            'blocks' => [],
        ]);

        self::assertSame(200, $status);
        self::assertSame([], $body['note']['blocks']);
    }

    public function testCreateAssignsIncrementingPositionsForSiblings(): void
    {
        $first = $this->createNote(title: 'A');
        $second = $this->createNote(title: 'B');
        $third = $this->createNote(title: 'C');

        self::assertSame(0, $first['position']);
        self::assertSame(1, $second['position']);
        self::assertSame(2, $third['position']);
    }

    public function testMoveToChildSetsParentId(): void
    {
        $parent = $this->createNote(title: 'parent');
        $child = $this->createNote(title: 'child');

        [$status, $body] = $this->postJson('backend_notes_block_move', ['id' => $child['id']], [
            'parentId' => $parent['id'],
        ]);

        self::assertSame(200, $status);
        self::assertSame($parent['id'], $body['note']['parentId']);
    }

    public function testMoveCannotCreateCycle(): void
    {
        $a = $this->createNote(title: 'A');
        $b = $this->createNote(title: 'B');

        $this->postJson('backend_notes_block_move', ['id' => $b['id']], ['parentId' => $a['id']]);

        [$status, $body] = $this->postJson('backend_notes_block_move', ['id' => $a['id']], ['parentId' => $b['id']]);

        self::assertSame(400, $status);
        self::assertFalse($body['success']);
        self::assertSame('cycle', $body['error']);
    }

    public function testReorderAppliesParentAndPositionFromEntries(): void
    {
        $a = $this->createNote(title: 'A');
        $b = $this->createNote(title: 'B');
        $c = $this->createNote(title: 'C');

        [$status] = $this->postJson('backend_notes_block_reorder', [], [
            'entries' => [
                ['id' => $c['id'], 'parentId' => null, 'position' => 0],
                ['id' => $a['id'], 'parentId' => $c['id'], 'position' => 0],
                ['id' => $b['id'], 'parentId' => null, 'position' => 1],
            ],
        ]);

        self::assertSame(200, $status);

        $repository = static::getContainer()->get(BlockNoteRepository::class);
        $reloadedA = $repository->find($a['id']);
        $reloadedB = $repository->find($b['id']);
        $reloadedC = $repository->find($c['id']);

        self::assertSame($reloadedC->getId(), $reloadedA->getParent()?->getId(), 'A should be under C');
        self::assertNull($reloadedB->getParent());
        self::assertNull($reloadedC->getParent());
        self::assertSame(0, $reloadedC->getPosition());
        self::assertSame(0, $reloadedA->getPosition());
        self::assertSame(1, $reloadedB->getPosition());
    }

    public function testReorderRejectsCycle(): void
    {
        $a = $this->createNote(title: 'A');
        $b = $this->createNote(title: 'B');

        [$status, $body] = $this->postJson('backend_notes_block_reorder', [], [
            'entries' => [
                ['id' => $a['id'], 'parentId' => $b['id'], 'position' => 0],
                ['id' => $b['id'], 'parentId' => $a['id'], 'position' => 0],
            ],
        ]);

        self::assertSame(400, $status);
        self::assertSame('cycle', $body['error']);
    }

    public function testDeleteRemovesTheNote(): void
    {
        $note = $this->createNote(title: 'doomed');
        $id = $note['id'];

        [$status, $body] = $this->postJson('backend_notes_block_delete', ['id' => $id], []);
        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        $repository = static::getContainer()->get(BlockNoteRepository::class);
        self::assertNull($repository->find($id));

        $this->createdNoteIds = array_values(array_filter($this->createdNoteIds, fn ($i) => $i !== $id));
    }

    public function testShowReturnsNotFoundForUnknownId(): void
    {
        [$status, $body] = $this->getJson('backend_notes_block_show', ['id' => 999999]);
        self::assertSame(404, $status);
        self::assertFalse($body['success']);
    }

    public function testSearchScansBlockTextualPayloads(): void
    {
        // Editor.js shapes vary per tool; the recursive search hits any
        // string leaf in `data` (paragraph.text, list.items[*].content,
        // table.content[][], callout.message, code.code, …).
        $hit = $this->createNote(title: 'A', blocks: [
            ['type' => 'paragraph', 'data' => ['text' => 'contains needle-marker here']],
        ]);
        $miss = $this->createNote(title: 'B', blocks: [
            ['type' => 'paragraph', 'data' => ['text' => 'no match']],
        ]);

        $this->client->request(
            HttpMethodEnum::Get->value,
            $this->urlGenerator->generate('backend_notes_block_search').'?q=needle-marker',
        );
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertContains($hit['id'], $body['ids']);
        self::assertNotContains($miss['id'], $body['ids']);
    }

    public function testTagsListReturnsAggregatedHistogram(): void
    {
        $this->createNote(title: 'A', tags: ['alpha', 'beta']);
        $this->createNote(title: 'B', tags: ['beta', 'gamma']);
        $this->createNote(title: 'C', tags: ['alpha']);

        [$status, $body] = $this->getJson('backend_notes_block_tags_list');

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        $byTag = [];
        foreach ($body['tags'] as $entry) {
            $byTag[$entry['tag']] = $entry['count'];
        }

        self::assertSame(2, $byTag['alpha'] ?? null);
        self::assertSame(2, $byTag['beta'] ?? null);
        self::assertSame(1, $byTag['gamma'] ?? null);
    }

    public function testTagsRenameRewritesOccurrencesAcrossNotes(): void
    {
        $a = $this->createNote(title: 'A', tags: ['old', 'kept']);
        $b = $this->createNote(title: 'B', tags: ['old']);

        [$status, $body] = $this->postJson('backend_notes_block_tags_rename', [], [
            'oldTag' => 'old',
            'newTag' => 'new',
        ]);
        self::assertSame(200, $status, 'body: '.json_encode($body));

        [, $reloadedA] = $this->getJson('backend_notes_block_show', ['id' => $a['id']]);
        [, $reloadedB] = $this->getJson('backend_notes_block_show', ['id' => $b['id']]);

        self::assertSame(['new', 'kept'], $reloadedA['note']['tags']);
        self::assertSame(['new'], $reloadedB['note']['tags']);
    }

    /**
     * @param list<string>                                                       $tags
     * @param list<array{id?: string, type: string, data: array<string, mixed>}> $blocks
     *
     * @return array<string, mixed>
     */
    private function createNote(
        string $title = 'Note',
        array $tags = [],
        array $blocks = [],
        ?int $parentId = null,
    ): array {
        [$status, $body] = $this->postJson('backend_notes_block_create', [], [
            'title' => $title,
            'tags' => $tags,
            'blocks' => $blocks,
            'parentId' => $parentId,
        ]);
        self::assertSame(200, $status, 'note creation should succeed; got body: '.json_encode($body));

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
