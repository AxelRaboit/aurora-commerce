<?php

declare(strict_types=1);

namespace Aurora\Tests\Integration\Controller;

use Aurora\Core\Enum\HttpMethodEnum;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Platform\User\Repository\UserRepository;
use Aurora\Tests\Integration\IntegrationTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FormsControllerTest extends IntegrationTestCase
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
        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_editorial_forms_list'));

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertIsArray($body['items']);
    }

    public function testCreateForm(): void
    {
        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload());

        self::assertSame(201, $status);
        self::assertTrue($body['success']);
        self::assertNotNull($body['form']['id']);
        self::assertEquals('Formulaire Test', $body['form']['translations']['fr']['title']);

        $this->cleanupForm($body['form']['id']);
    }

    public function testCreateFormWithDuplicateSlugFails(): void
    {
        $suffix = uniqid();
        [, $first] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload($suffix));
        self::assertTrue($first['success']);

        [$status, $second] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload($suffix));
        self::assertSame(422, $status);
        self::assertFalse($second['success']);
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

        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $payload);

        self::assertSame(422, $status);
        self::assertFalse($body['success']);
    }

    public function testGetForm(): void
    {
        [, $created] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload());
        $id = $created['form']['id'];

        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_editorial_forms_get', ['id' => $id]));

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame($id, $body['form']['id']);
        self::assertIsArray($body['form']['fields']);

        $this->cleanupForm($id);
    }

    public function testUpdateForm(): void
    {
        [, $created] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload());
        $id = $created['form']['id'];

        $suffix = uniqid();
        $updated = $this->createFormPayload($suffix);
        $updated['translations']['fr']['title'] = 'Formulaire Modifié';

        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_update', ['id' => $id]), $updated);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame('Formulaire Modifié', $body['form']['translations']['fr']['title']);

        $this->cleanupForm($id);
    }

    public function testCreateField(): void
    {
        [, $created] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload());
        $id = $created['form']['id'];

        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_field_create', ['id' => $id]), $this->createFieldPayload());

        self::assertSame(201, $status);
        self::assertTrue($body['success']);
        self::assertNotNull($body['field']['id']);
        self::assertSame('text', $body['field']['type']);
        self::assertTrue($body['field']['required']);
        self::assertSame('Nom', $body['field']['translations']['fr']['label']);

        $this->cleanupForm($id);
    }

    public function testCreateFieldWithoutLabelFails(): void
    {
        [, $created] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload());
        $id = $created['form']['id'];

        $payload = ['type' => 'text', 'required' => false, 'translations' => []];
        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_field_create', ['id' => $id]), $payload);

        self::assertSame(422, $status);
        self::assertFalse($body['success']);

        $this->cleanupForm($id);
    }

    public function testUpdateField(): void
    {
        [, $created] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload());
        $formId = $created['form']['id'];

        [, $fieldCreated] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_field_create', ['id' => $formId]), $this->createFieldPayload());
        $fieldId = $fieldCreated['field']['id'];

        $updated = $this->createFieldPayload('Prénom');
        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_field_update', ['id' => $formId, 'fieldId' => $fieldId]), $updated);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
        self::assertSame('Prénom', $body['field']['translations']['fr']['label']);

        $this->cleanupForm($formId);
    }

    public function testDeleteField(): void
    {
        [, $created] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload());
        $formId = $created['form']['id'];

        [, $fieldCreated] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_field_create', ['id' => $formId]), $this->createFieldPayload());
        $fieldId = $fieldCreated['field']['id'];

        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_field_delete', ['id' => $formId, 'fieldId' => $fieldId]));

        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        [, $formBody] = $this->jsonRequest(HttpMethodEnum::Get->value, $this->urlGenerator->generate('backend_editorial_forms_get', ['id' => $formId]));
        self::assertCount(0, $formBody['form']['fields']);

        $this->cleanupForm($formId);
    }

    public function testReorderFields(): void
    {
        [, $created] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload());
        $formId = $created['form']['id'];

        [, $f1] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_field_create', ['id' => $formId]), $this->createFieldPayload('Champ 1'));
        [, $f2] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_field_create', ['id' => $formId]), $this->createFieldPayload('Champ 2'));

        $orderedIds = [$f2['field']['id'], $f1['field']['id']];
        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_field_reorder', ['id' => $formId]), ['orderedIds' => $orderedIds]);

        self::assertSame(200, $status);
        self::assertTrue($body['success']);

        $this->cleanupForm($formId);
    }

    public function testDeleteForm(): void
    {
        [, $created] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_create'), $this->createFormPayload());
        $id = $created['form']['id'];

        [$status, $body] = $this->jsonRequest(HttpMethodEnum::Post->value, $this->urlGenerator->generate('backend_editorial_forms_delete', ['id' => $id]));

        self::assertSame(200, $status);
        self::assertTrue($body['success']);
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
