<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Module\Project\Dto\ProjectInput;
use Aurora\Module\Project\Enum\ProjectStatusEnum;
use Aurora\Module\Project\Manager\ProjectManager;
use Aurora\Tests\Integration\IntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProjectsControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'dev@aurora.app', 'type' => 'backend']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');

        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
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

    public function testListReturnsPagination(): void
    {
        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_projects_list'));

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertArrayHasKey('items', $body);
        self::assertArrayHasKey('total', $body);
        self::assertArrayHasKey('page', $body);
    }

    public function testCreateValidatesAndPersists(): void
    {
        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_projects_create'), [
            'title' => 'Test Project',
            'status' => ProjectStatusEnum::Draft->value,
        ]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertArrayHasKey('project', $body);
        self::assertSame('Test Project', $body['project']['title']);
        self::assertNotNull($body['project']['reference']);
    }

    public function testCreateRejectsEmptyTitle(): void
    {
        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_projects_create'), [
            'title' => '',
            'status' => ProjectStatusEnum::Draft->value,
        ]);

        self::assertSame(422, $status);
        self::assertFalse($body['success']);
        self::assertArrayHasKey('errors', $body);
        self::assertArrayHasKey('title', $body['errors']);
    }

    public function testShowReturnsSerializedProjectAndTasks(): void
    {
        $manager = static::getContainer()->get(ProjectManager::class);
        $project = $manager->create(new ProjectInput(title: 'Show me', status: ProjectStatusEnum::Active->value));

        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_projects_show', ['id' => $project->getId()]));

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame('Show me', $body['project']['title']);
        self::assertArrayHasKey('columns', $body['project']);
        self::assertArrayHasKey('tasks', $body);
    }
}
