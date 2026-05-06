<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Core\User\Entity\User;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Editorial\Post\Entity\Post;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Editorial\Post\Service\PostTextExtractor;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SearchControllerTest extends IntegrationTestCase
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

    private function createPost(string $title, array $blocks = []): Post
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $postType = $container->get(PostTypeRepository::class)->findOneBy([]);
        $extractor = $container->get(PostTextExtractor::class);

        $post = (new Post())->setPostType($postType)->setStatus(PostStatusEnum::Draft);
        $translation = $post->translate('fr');
        $translation->setTitle($title);
        $translation->setSlug(mb_strtolower(str_replace(' ', '-', $title)));
        $translation->setBlocks($blocks);
        $translation->setSearchContent($extractor->extract($translation));

        $entityManager->persist($post);
        $entityManager->flush();

        return $post;
    }

    public function testSearchMatchesContentInBlocks(): void
    {
        $target = $this->createPost('Random title', [
            ['type' => 'paragraph', 'data' => ['text' => 'This paragraph mentions constellation patterns.']],
        ]);
        $noise = $this->createPost('Unrelated', [
            ['type' => 'paragraph', 'data' => ['text' => 'Nothing interesting here.']],
        ]);

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_search', ['q' => 'constellation']));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);

        $ids = array_map(static fn (array $result): int => $result['id'], $body['posts']);
        self::assertContains($target->getId(), $ids);
        self::assertNotContains($noise->getId(), $ids);
    }

    public function testSearchReturnsSnippetAroundMatch(): void
    {
        $post = $this->createPost('Article', [
            ['type' => 'paragraph', 'data' => ['text' => 'Lorem ipsum dolor sit amet, constellation patterns continue beyond the sky.']],
        ]);

        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_search', ['q' => 'constellation']));
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        $match = array_values(array_filter($body['posts'], static fn (array $result): bool => $result['id'] === $post->getId()))[0] ?? null;
        self::assertNotNull($match);
        self::assertStringContainsStringIgnoringCase('constellation', (string) $match['snippet']);
    }

    public function testSearchIncludesTerms(): void
    {
        $this->client->request(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_search', ['q' => 'nouveaut']));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);

        // The fixtures seed the "Nouveauté" term under the tag taxonomy.
        $names = array_map(static fn (array $term): ?string => $term['name'], $body['terms']);
        self::assertContains('Nouveauté', $names);
    }
}
