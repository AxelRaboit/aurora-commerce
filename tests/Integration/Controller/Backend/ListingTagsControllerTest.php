<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller\Backend;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Repository\ListingTagRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ListingTagsControllerTest extends IntegrationTestCase
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
        $repository = static::getContainer()->get(ListingTagRepository::class);

        foreach ($this->createdTagIds as $tagId) {
            $tag = $repository->find($tagId);
            if ($tag instanceof ListingTagInterface) {
                $entityManager->remove($tag);
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
    private function createTag(string $name = 'Tag', string $color = '#FF00AA'): array
    {
        [$status, $body] = $this->postJson('backend_ecommerce_listing_tags_create', [], [
            'color' => $color,
            'isVisible' => true,
            'translations' => [
                'en' => [
                    'name' => $name,
                    'slug' => mb_strtolower($name).'-'.bin2hex(random_bytes(3)),
                    'description' => null,
                ],
            ],
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
            $this->urlGenerator->generate('backend_ecommerce_listing_tags'),
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateUpdateAndDelete(): void
    {
        $tag = $this->createTag('Alpha', '#112233');
        self::assertSame('Alpha', $tag['translations']['en']['name'] ?? null);
        self::assertSame('#112233', $tag['color']);

        // Update
        [$status, $body] = $this->postJson(
            'backend_ecommerce_listing_tags_update',
            ['id' => $tag['id']],
            [
                'color' => '#445566',
                'isVisible' => false,
                'translations' => [
                    'en' => [
                        'name' => 'AlphaRenamed',
                        'slug' => 'alpha-renamed-'.bin2hex(random_bytes(3)),
                        'description' => 'Updated',
                    ],
                ],
            ],
        );
        self::assertSame(200, $status, json_encode($body));
        self::assertTrue($body['success']);
        self::assertSame('#445566', $body['tag']['color']);
        self::assertSame('AlphaRenamed', $body['tag']['translations']['en']['name'] ?? null);
        self::assertFalse($body['tag']['isVisible']);

        // Delete
        [$status, $body] = $this->postJson(
            'backend_ecommerce_listing_tags_delete',
            ['id' => $tag['id']],
            [],
        );
        self::assertSame(200, $status, json_encode($body));
        self::assertTrue($body['success']);

        $repository = static::getContainer()->get(ListingTagRepository::class);
        static::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull($repository->find($tag['id']));

        // Already deleted, drop from cleanup list
        $this->createdTagIds = array_values(array_filter($this->createdTagIds, static fn (int $id): bool => $id !== (int) $tag['id']));
    }

    public function testCreateWithInvalidColorFails(): void
    {
        [$status, $body] = $this->postJson('backend_ecommerce_listing_tags_create', [], [
            'color' => 'not-a-hex',
            'isVisible' => true,
            'translations' => [
                'en' => ['name' => 'Beta', 'slug' => 'beta-'.bin2hex(random_bytes(3)), 'description' => null],
            ],
        ]);

        self::assertSame(422, $status, json_encode($body));
        self::assertFalse($body['success']);
    }
}
