<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Notes\Markdown\Entity\MarkdownNoteInterface;
use Aurora\Module\Notes\Markdown\Repository\MarkdownNoteRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MarkdownNotesControllerTest extends IntegrationTestCase
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
        $repository = static::getContainer()->get(MarkdownNoteRepository::class);

        foreach ($this->createdNoteIds as $id) {
            $note = $repository->find($id);
            if ($note instanceof MarkdownNoteInterface) {
                $entityManager->remove($note);
            }
        }
        $entityManager->flush();
        $entityManager->clear();

        parent::tearDown();
    }

    public function testIndexReturnsEmptyListForFreshUser(): void
    {
        [$status, $body] = $this->getJson('backend_notes_markdown_list');

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame([], $body['notes']);
    }

    public function testIndexPageRendersTwigTemplate(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_notes_markdown'));
        $response = $this->client->getResponse();
        $content = (string) $response->getContent();

        self::assertSame(200, $response->getStatusCode(), 'response body: '.mb_substr($content, 0, 800));
        self::assertStringContainsString('notes/backend/markdown/MarkdownNotesApp', $content);
    }

    public function testCreateThenShowRoundtripsContent(): void
    {
        $created = $this->createNote(title: 'My first note', content: "# Hello\n\nBody here.", tags: ['draft']);

        self::assertSame('My first note', $created['title']);
        self::assertSame("# Hello\n\nBody here.", $created['content']);
        self::assertSame(['draft'], $created['tags']);
        self::assertSame(0, $created['position']);
        self::assertNull($created['parentId']);

        [$status, $body] = $this->getJson('backend_notes_markdown_show', ['id' => $created['id']]);
        self::assertSame(200, $status);
        self::assertSame($created['content'], $body['note']['content']);
    }

    public function testCreatedRowOnDatabaseIsEncrypted(): void
    {
        $note = $this->createNote(title: 'secret-marker-xyz', content: 'plaintext-marker-xyz');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $row = $entityManager->getConnection()
            ->fetchAssociative('SELECT title, content FROM core_markdown_notes WHERE id = :id', ['id' => $note['id']]);

        self::assertIsArray($row);
        self::assertNotSame('secret-marker-xyz', $row['title']);
        self::assertNotSame('plaintext-marker-xyz', $row['content']);
        self::assertStringNotContainsString('plaintext-marker-xyz', (string) $row['content']);
    }

    public function testUpdateChangesFields(): void
    {
        $note = $this->createNote(title: 'old');

        [$status, $body] = $this->postJson('backend_notes_markdown_update', ['id' => $note['id']], [
            'title' => 'new',
            'content' => 'updated body',
            'tags' => ['urgent', 'work'],
        ]);

        self::assertSame(200, $status);
        self::assertSame('new', $body['note']['title']);
        self::assertSame('updated body', $body['note']['content']);
        self::assertSame(['urgent', 'work'], $body['note']['tags']);
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

        [$status, $body] = $this->postJson('backend_notes_markdown_move', ['id' => $child['id']], [
            'parentId' => $parent['id'],
        ]);

        self::assertSame(200, $status);
        self::assertSame($parent['id'], $body['note']['parentId']);
    }

    public function testMoveCannotCreateCycle(): void
    {
        $a = $this->createNote(title: 'A');
        $b = $this->createNote(title: 'B');

        // make B a child of A
        $this->postJson('backend_notes_markdown_move', ['id' => $b['id']], ['parentId' => $a['id']]);

        // try to move A under B — would create a cycle
        [$status, $body] = $this->postJson('backend_notes_markdown_move', ['id' => $a['id']], ['parentId' => $b['id']]);

        self::assertSame(400, $status);
        self::assertFalse($body['success']);
        self::assertSame('cycle', $body['error']);
    }

    public function testReorderAppliesParentAndPositionFromEntries(): void
    {
        $a = $this->createNote(title: 'A');
        $b = $this->createNote(title: 'B');
        $c = $this->createNote(title: 'C');

        [$status] = $this->postJson('backend_notes_markdown_reorder', [], [
            'entries' => [
                ['id' => $c['id'], 'parentId' => null, 'position' => 0],
                ['id' => $a['id'], 'parentId' => $c['id'], 'position' => 0],
                ['id' => $b['id'], 'parentId' => null, 'position' => 1],
            ],
        ]);

        self::assertSame(200, $status);

        $repository = static::getContainer()->get(MarkdownNoteRepository::class);
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

        [$status, $body] = $this->postJson('backend_notes_markdown_reorder', [], [
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

        [$status, $body] = $this->postJson('backend_notes_markdown_delete', ['id' => $id], []);
        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        $repository = static::getContainer()->get(MarkdownNoteRepository::class);
        self::assertNull($repository->find($id));

        // remove from cleanup list since it's already gone
        $this->createdNoteIds = array_values(array_filter($this->createdNoteIds, fn ($i) => $i !== $id));
    }

    public function testShowReturnsNotFoundForUnknownId(): void
    {
        [$status, $body] = $this->getJson('backend_notes_markdown_show', ['id' => 999999]);
        self::assertSame(404, $status);
        self::assertFalse($body['success']);
    }

    public function testRenamingTitleRewritesWikiLinksInOtherNotes(): void
    {
        $target = $this->createNote(title: 'Old Title');
        $referrer = $this->createNote(title: 'Referrer', content: 'See [[Old Title]] for context.');

        [$status] = $this->postJson('backend_notes_markdown_update', ['id' => $target['id']], [
            'title' => 'New Title',
        ]);
        self::assertSame(200, $status);

        [, $body] = $this->getJson('backend_notes_markdown_show', ['id' => $referrer['id']]);
        self::assertSame('See [[New Title]] for context.', $body['note']['content']);
    }

    public function testBacklinksEndpointReturnsLinkingNotes(): void
    {
        $target = $this->createNote(title: 'TargetPage');
        $linker = $this->createNote(title: 'Linker', content: 'goes to [[targetpage]] here');
        $this->createNote(title: 'Unrelated', content: 'nothing to see');

        [$status, $body] = $this->getJson('backend_notes_markdown_backlinks', ['id' => $target['id']]);

        self::assertSame(200, $status);
        self::assertCount(1, $body['backlinks']);
        self::assertSame($linker['id'], $body['backlinks'][0]['id']);
    }

    public function testUnlinkedMentionsEndpointExcludesProperLinks(): void
    {
        $target = $this->createNote(title: 'Foo');
        $linker = $this->createNote(title: 'L', content: 'See [[foo]]');
        $mentioner = $this->createNote(title: 'M', content: 'I love foo but not linked');

        [$status, $body] = $this->getJson('backend_notes_markdown_unlinked_mentions', ['id' => $target['id']]);

        self::assertSame(200, $status);
        self::assertCount(1, $body['mentions']);
        self::assertSame($mentioner['id'], $body['mentions'][0]['id']);
    }

    public function testTagsListReturnsAggregatedHistogram(): void
    {
        $this->createNote(title: 'A', tags: ['alpha', 'beta']);
        $this->createNote(title: 'B', tags: ['beta', 'gamma']);
        $this->createNote(title: 'C', tags: ['alpha']);

        [$status, $body] = $this->getJson('backend_notes_markdown_tags_list');

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

    public function testGraphEndpointReturnsNodesAndEdges(): void
    {
        $a = $this->createNote(title: 'Alpha', content: 'links to [[Beta]]');
        $b = $this->createNote(title: 'Beta', content: 'no outgoing');

        [$status, $body] = $this->getJson('backend_notes_markdown_graph');

        self::assertSame(200, $status);
        // node count may include other notes created in earlier passing assertions reused via shared fixtures…
        // …but the edges Alpha→Beta must exist
        $hasEdge = false;
        foreach ($body['edges'] as $edge) {
            if ($edge['source'] === $a['id'] && $edge['target'] === $b['id']) {
                $hasEdge = true;
                break;
            }
        }
        self::assertTrue($hasEdge, 'graph must contain Alpha→Beta edge');
    }

    /**
     * @param list<string> $tags
     *
     * @return array<string, mixed>
     */
    private function createNote(
        string $title = 'Note',
        string $content = '',
        array $tags = [],
        ?int $parentId = null,
    ): array {
        [$status, $body] = $this->postJson('backend_notes_markdown_create', [], [
            'title' => $title,
            'content' => $content,
            'tags' => $tags,
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
