<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Entity\Form;
use App\Entity\User;
use App\Repository\User\UserRepository;
use App\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class FormsControllerTest extends IntegrationTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $admin = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'admin@velox.app']);
        self::assertInstanceOf(User::class, $admin);
        $this->client->loginUser($admin, 'admin');
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

    private function createFormPayload(string $slugSuffix = ''): array
    {
        $suffix = '' !== $slugSuffix ? $slugSuffix : uniqid();

        return [
            'notifyEmail' => null,
            'active' => true,
            'translations' => [
                'fr' => ['title' => 'Formulaire Test', 'slug' => 'formulaire-test-'.$suffix, 'description' => null],
                'en' => ['title' => 'Test Form', 'slug' => 'test-form-'.$suffix, 'description' => null],
            ],
        ];
    }

    private function createFieldPayload(string $label = 'Nom'): array
    {
        return [
            'type' => 'text',
            'required' => true,
            'translations' => [
                'fr' => ['label' => $label, 'placeholder' => null, 'options' => []],
                'en' => ['label' => 'Name', 'placeholder' => null, 'options' => []],
            ],
        ];
    }

    public function testListReturnsOk(): void
    {
        [$status, $body] = $this->jsonRequest('GET', '/admin/forms/list');

        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
        self::assertIsArray($body['items']);
    }

    public function testCreateForm(): void
    {
        [$status, $body] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload());

        self::assertSame(201, $status);
        self::assertTrue($body['ok']);
        self::assertNotNull($body['form']['id']);
        self::assertEquals('Formulaire Test', $body['form']['translations']['fr']['title']);

        $this->cleanupForm($body['form']['id']);
    }

    public function testCreateFormWithDuplicateSlugFails(): void
    {
        $suffix = uniqid();
        [, $first] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload($suffix));
        self::assertTrue($first['ok']);

        [$status, $second] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload($suffix));
        self::assertSame(422, $status);
        self::assertFalse($second['ok']);
        self::assertNotEmpty($second['errors']);

        $this->cleanupForm($first['form']['id']);
    }

    public function testCreateFormWithoutTitleFails(): void
    {
        $payload = [
            'notifyEmail' => null,
            'active' => true,
            'translations' => ['fr' => ['title' => '', 'slug' => '', 'description' => null]],
        ];

        [$status, $body] = $this->jsonRequest('POST', '/admin/forms', $payload);

        self::assertSame(422, $status);
        self::assertFalse($body['ok']);
    }

    public function testGetForm(): void
    {
        [, $created] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload());
        $id = $created['form']['id'];

        [$status, $body] = $this->jsonRequest('GET', "/admin/forms/{$id}");

        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
        self::assertSame($id, $body['form']['id']);
        self::assertIsArray($body['form']['fields']);

        $this->cleanupForm($id);
    }

    public function testUpdateForm(): void
    {
        [, $created] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload());
        $id = $created['form']['id'];

        $suffix = uniqid();
        $updated = $this->createFormPayload($suffix);
        $updated['translations']['fr']['title'] = 'Formulaire Modifié';

        [$status, $body] = $this->jsonRequest('POST', "/admin/forms/{$id}/edit", $updated);

        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
        self::assertSame('Formulaire Modifié', $body['form']['translations']['fr']['title']);

        $this->cleanupForm($id);
    }

    public function testCreateField(): void
    {
        [, $created] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload());
        $id = $created['form']['id'];

        [$status, $body] = $this->jsonRequest('POST', "/admin/forms/{$id}/fields", $this->createFieldPayload());

        self::assertSame(201, $status);
        self::assertTrue($body['ok']);
        self::assertNotNull($body['field']['id']);
        self::assertSame('text', $body['field']['type']);
        self::assertTrue($body['field']['required']);
        self::assertSame('Nom', $body['field']['translations']['fr']['label']);

        $this->cleanupForm($id);
    }

    public function testCreateFieldWithoutLabelFails(): void
    {
        [, $created] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload());
        $id = $created['form']['id'];

        $payload = ['type' => 'text', 'required' => false, 'translations' => []];
        [$status, $body] = $this->jsonRequest('POST', "/admin/forms/{$id}/fields", $payload);

        self::assertSame(422, $status);
        self::assertFalse($body['ok']);

        $this->cleanupForm($id);
    }

    public function testUpdateField(): void
    {
        [, $created] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload());
        $formId = $created['form']['id'];

        [, $fieldCreated] = $this->jsonRequest('POST', "/admin/forms/{$formId}/fields", $this->createFieldPayload());
        $fieldId = $fieldCreated['field']['id'];

        $updated = $this->createFieldPayload('Prénom');
        [$status, $body] = $this->jsonRequest('POST', "/admin/forms/{$formId}/fields/{$fieldId}/edit", $updated);

        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
        self::assertSame('Prénom', $body['field']['translations']['fr']['label']);

        $this->cleanupForm($formId);
    }

    public function testDeleteField(): void
    {
        [, $created] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload());
        $formId = $created['form']['id'];

        [, $fieldCreated] = $this->jsonRequest('POST', "/admin/forms/{$formId}/fields", $this->createFieldPayload());
        $fieldId = $fieldCreated['field']['id'];

        [$status, $body] = $this->jsonRequest('POST', "/admin/forms/{$formId}/fields/{$fieldId}/delete");

        self::assertSame(200, $status);
        self::assertTrue($body['ok']);

        [, $formBody] = $this->jsonRequest('GET', "/admin/forms/{$formId}");
        self::assertCount(0, $formBody['form']['fields']);

        $this->cleanupForm($formId);
    }

    public function testReorderFields(): void
    {
        [, $created] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload());
        $formId = $created['form']['id'];

        [, $f1] = $this->jsonRequest('POST', "/admin/forms/{$formId}/fields", $this->createFieldPayload('Champ 1'));
        [, $f2] = $this->jsonRequest('POST', "/admin/forms/{$formId}/fields", $this->createFieldPayload('Champ 2'));

        $orderedIds = [$f2['field']['id'], $f1['field']['id']];
        [$status, $body] = $this->jsonRequest('POST', "/admin/forms/{$formId}/fields/reorder", ['orderedIds' => $orderedIds]);

        self::assertSame(200, $status);
        self::assertTrue($body['ok']);

        $this->cleanupForm($formId);
    }

    public function testDeleteForm(): void
    {
        [, $created] = $this->jsonRequest('POST', '/admin/forms', $this->createFormPayload());
        $id = $created['form']['id'];

        [$status, $body] = $this->jsonRequest('POST', "/admin/forms/{$id}/delete");

        self::assertSame(200, $status);
        self::assertTrue($body['ok']);
    }

    private function cleanupForm(int $id): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $form = $entityManager->find(Form::class, $id);
        if ($form instanceof Form) {
            $entityManager->remove($form);
            $entityManager->flush();
        }
    }
}
