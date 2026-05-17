<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\Platform\User\Entity\User;
use Aurora\Core\Platform\User\Repository\UserRepository;
use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Aurora\Module\Crm\ContactTag\Repository\ContactTagRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ContactTagsControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;
    /** @var list<int> */
    private array $createdTagIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->createdTagIds = [];
    }

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $repository = static::getContainer()->get(ContactTagRepository::class);

        foreach ($this->createdTagIds as $tagId) {
            $contactTag = $repository->find($tagId);
            if ($contactTag instanceof ContactTagInterface) {
                $entityManager->remove($contactTag);
            }
        }
        $entityManager->flush();
        $entityManager->clear();

        parent::tearDown();
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

    /** @return array<string, mixed> */
    private function createTag(string $label = 'VIP', string $color = '#FF00AA'): array
    {
        $suffix = bin2hex(random_bytes(3));
        [$status, $body] = $this->postJson('backend_crm_contact_tags_create', [], [
            'label' => $label.'-'.$suffix,
            'slug' => mb_strtolower($label).'-'.$suffix,
            'color' => $color,
        ]);
        self::assertSame(200, $status, json_encode($body));
        self::assertTrue($body['success'], json_encode($body));

        $this->createdTagIds[] = (int) $body['tag']['id'];

        return $body['tag'];
    }

    public function testIndexReturnsOk(): void
    {
        $this->client->request(
            HttpMethodEnum::Get->value,
            $this->urlGenerator->generate('backend_crm_contact_tags'),
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateUpdateAndDelete(): void
    {
        $contactTag = $this->createTag('Alpha', '#112233');
        self::assertStringStartsWith('Alpha-', (string) ($contactTag['label'] ?? ''));
        self::assertSame('#112233', $contactTag['color']);

        [$status, $body] = $this->postJson(
            'backend_crm_contact_tags_update',
            ['id' => $contactTag['id']],
            [
                'label' => 'AlphaRenamed',
                'slug' => 'alpha-renamed-'.bin2hex(random_bytes(3)),
                'color' => '#445566',
            ],
        );
        self::assertSame(200, $status, json_encode($body));
        self::assertTrue($body['success']);
        self::assertSame('#445566', $body['tag']['color']);
        self::assertSame('AlphaRenamed', $body['tag']['label']);

        [$status, $body] = $this->postJson(
            'backend_crm_contact_tags_delete',
            ['id' => $contactTag['id']],
            [],
        );
        self::assertSame(200, $status, json_encode($body));
        self::assertTrue($body['success']);

        $repository = static::getContainer()->get(ContactTagRepository::class);
        static::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull($repository->find($contactTag['id']));

        $this->createdTagIds = array_values(array_filter($this->createdTagIds, static fn (int $id): bool => $id !== (int) $contactTag['id']));
    }

    public function testCreateAutoDerivesSlugWhenEmpty(): void
    {
        $suffix = bin2hex(random_bytes(3));
        [$status, $body] = $this->postJson('backend_crm_contact_tags_create', [], [
            'label' => 'Hot Lead '.$suffix,
            'slug' => '',
            'color' => '#123456',
        ]);

        self::assertSame(200, $status, json_encode($body));
        self::assertTrue($body['success']);
        self::assertNotSame('', $body['tag']['slug']);
        self::assertStringContainsString('hot-lead', $body['tag']['slug']);

        $this->createdTagIds[] = (int) $body['tag']['id'];
    }

    public function testCreateWithInvalidColorFails(): void
    {
        [$status, $body] = $this->postJson('backend_crm_contact_tags_create', [], [
            'label' => 'Beta',
            'slug' => 'beta',
            'color' => 'not-a-hex',
        ]);

        self::assertSame(422, $status, json_encode($body));
        self::assertFalse($body['success']);
    }

    public function testCreateWithEmptyLabelFails(): void
    {
        [$status, $body] = $this->postJson('backend_crm_contact_tags_create', [], [
            'label' => '',
            'slug' => '',
            'color' => '#6366F1',
        ]);

        self::assertSame(422, $status, json_encode($body));
        self::assertFalse($body['success']);
    }
}
