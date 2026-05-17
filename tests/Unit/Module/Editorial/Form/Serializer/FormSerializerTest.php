<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Form\Serializer;

use Aurora\Module\Editorial\Form\Entity\AbstractForm;
use Aurora\Module\Editorial\Form\Entity\AbstractFormSubmission;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslation;
use Aurora\Module\Editorial\Form\Entity\FormSubmission;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Aurora\Module\Editorial\Form\Serializer\FormSerializer;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class FormSerializerTest extends TestCase
{
    private FormSerializer $serializer;

    protected function setUp(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $key): string => "tr({$key})");

        $this->serializer = new FormSerializer($translator);
    }

    public function testSerializeProjectsTopLevelFieldsAndFormTranslations(): void
    {
        $form = $this->makeForm(id: 5, notifyEmail: 'ops@example.com', webhookUrl: 'https://hooks.example.com/x', crmSync: true, steps: ['step1', 'step2'], active: true);
        $this->addFormTranslation($form, 'fr', title: 'Contact', slug: 'contact', description: 'Form description');
        $this->addFormTranslation($form, 'en', title: 'Contact us', slug: 'contact-us', description: null);

        $payload = $this->serializer->serialize($form);

        self::assertSame(5, $payload['id']);
        self::assertSame('ops@example.com', $payload['notifyEmail']);
        self::assertSame('https://hooks.example.com/x', $payload['webhookUrl']);
        self::assertTrue($payload['crmSync']);
        self::assertSame(['step1', 'step2'], $payload['steps']);
        self::assertTrue($payload['active']);
        self::assertSame(0, $payload['submissionCount']);
        self::assertSame('Contact', $payload['translations']['fr']['title']);
        self::assertSame('contact-us', $payload['translations']['en']['slug']);
        self::assertNull($payload['translations']['en']['description']);
    }

    public function testSerializeIncludesFieldsByDefault(): void
    {
        $form = $this->makeForm();
        $form->getFields()->add($this->makeField(id: 10, type: FormFieldTypeEnum::Text, position: 0));
        $form->getFields()->add($this->makeField(id: 11, type: FormFieldTypeEnum::Email, position: 1));

        $payload = $this->serializer->serialize($form);

        self::assertCount(2, $payload['fields']);
        self::assertSame('text', $payload['fields'][0]['type']);
        self::assertSame('email', $payload['fields'][1]['type']);
    }

    public function testSerializeOmitsFieldsWhenRequested(): void
    {
        // Admin list view passes `withFields=false` to avoid the N+1 over
        // every form's field collection.
        $form = $this->makeForm();
        $form->getFields()->add($this->makeField(id: 10, type: FormFieldTypeEnum::Text));

        $payload = $this->serializer->serialize($form, withFields: false);

        self::assertArrayNotHasKey('fields', $payload);
    }

    public function testSerializeFieldExposesTypeAndConditionsWithDefaults(): void
    {
        $field = $this->makeField(id: 7, type: FormFieldTypeEnum::Select, required: true, position: 3, step: 1);

        $payload = $this->serializer->serializeField($field);

        self::assertSame(7, $payload['id']);
        self::assertSame('select', $payload['type']);
        self::assertStringStartsWith('tr(', $payload['typeLabel']);
        self::assertTrue($payload['required']);
        self::assertSame(3, $payload['position']);
        self::assertSame(1, $payload['step']);
        // Defaults : conditions → empty list, conditionsLogic → 'and'.
        self::assertSame([], $payload['conditions']);
        self::assertSame('and', $payload['conditionsLogic']);
    }

    public function testSerializeFieldPreservesCustomConditionsAndLogic(): void
    {
        $field = $this->makeField(id: 1);
        $conditions = [['fieldId' => 10, 'operator' => 'eq', 'value' => 'yes']];
        $field->setConditions($conditions);
        $field->setConditionsLogic('or');

        $payload = $this->serializer->serializeField($field);

        self::assertSame($conditions, $payload['conditions']);
        self::assertSame('or', $payload['conditionsLogic']);
    }

    public function testSerializeFieldEmitsTranslationsKeyedByLocale(): void
    {
        $field = $this->makeField(id: 1);
        $this->addFieldTranslation($field, 'fr', label: 'Nom', placeholder: 'Votre nom');
        $this->addFieldTranslation($field, 'en', label: 'Name', options: ['choice-a', 'choice-b']);

        $payload = $this->serializer->serializeField($field);

        self::assertSame('Nom', $payload['translations']['fr']['label']);
        self::assertSame('Votre nom', $payload['translations']['fr']['placeholder']);
        self::assertSame('Name', $payload['translations']['en']['label']);
        self::assertSame(['choice-a', 'choice-b'], $payload['translations']['en']['options']);
    }

    public function testSerializeFieldForLocalePicksTheRequestedLocale(): void
    {
        $field = $this->makeField(id: 1);
        $this->addFieldTranslation($field, 'fr', label: 'Nom FR', placeholder: 'placeholder fr');
        $this->addFieldTranslation($field, 'en', label: 'Name EN');

        $payload = $this->serializer->serializeFieldForLocale($field, 'en');

        self::assertSame('Name EN', $payload['label']);
        // Front rendering doesn't expose `translations` (just the
        // already-localized field).
        self::assertArrayNotHasKey('translations', $payload);
    }

    public function testSerializeFieldForLocaleFallsBackToFirstAvailableTranslation(): void
    {
        // User requests 'es' but only 'fr' is authored — fall back rather
        // than render an empty label.
        $field = $this->makeField(id: 1);
        $this->addFieldTranslation($field, 'fr', label: 'Nom FR', placeholder: 'placeholder fr');

        $payload = $this->serializer->serializeFieldForLocale($field, 'es');

        self::assertSame('Nom FR', $payload['label']);
        self::assertSame('placeholder fr', $payload['placeholder']);
    }

    public function testSerializeFieldForLocaleReturnsEmptyDefaultsWhenNoTranslations(): void
    {
        // Field freshly created without any translation yet.
        $field = $this->makeField(id: 1);

        $payload = $this->serializer->serializeFieldForLocale($field, 'fr');

        self::assertSame('', $payload['label']);
        self::assertNull($payload['placeholder']);
        self::assertSame([], $payload['options']);
    }

    public function testSerializeSubmissionProjectsSubmittedAtAsAtom(): void
    {
        $submission = new FormSubmission();
        (new ReflectionProperty(FormSubmission::class, 'id'))->setValue($submission, 42);
        (new ReflectionProperty(AbstractFormSubmission::class, 'submittedAt'))
            ->setValue($submission, new DateTimeImmutable('2026-03-10T08:00:00+00:00'));
        $submission->setLocale('fr');
        $submission->setIp('1.2.3.4');
        $submission->setData(['name' => 'Alice', 'email' => 'a@example.com']);

        $payload = $this->serializer->serializeSubmission($submission);

        self::assertSame(42, $payload['id']);
        self::assertSame('2026-03-10T08:00:00+00:00', $payload['submittedAt']);
        self::assertSame('fr', $payload['locale']);
        self::assertSame('1.2.3.4', $payload['ip']);
        self::assertSame(['name' => 'Alice', 'email' => 'a@example.com'], $payload['data']);
    }

    // ── Fixture helpers ─────────────────────────────────────────────

    /** @param list<mixed>|null $steps */
    private function makeForm(
        int $id = 1,
        ?string $notifyEmail = null,
        ?string $webhookUrl = null,
        bool $crmSync = false,
        ?array $steps = null,
        bool $active = true,
    ): Form {
        $form = new Form();
        (new ReflectionProperty(Form::class, 'id'))->setValue($form, $id);
        $form->setNotifyEmail($notifyEmail);
        $form->setWebhookUrl($webhookUrl);
        $form->setCrmSync($crmSync);
        $form->setSteps($steps);
        $form->setActive($active);

        $now = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        (new ReflectionProperty(AbstractForm::class, 'createdAt'))->setValue($form, $now);
        (new ReflectionProperty(AbstractForm::class, 'updatedAt'))->setValue($form, $now);

        return $form;
    }

    private function makeField(
        int $id,
        FormFieldTypeEnum $type = FormFieldTypeEnum::Text,
        bool $required = false,
        int $position = 0,
        ?int $step = null,
    ): FormField {
        $field = new FormField();
        (new ReflectionProperty(FormField::class, 'id'))->setValue($field, $id);
        $field->setType($type);
        $field->setRequired($required);
        $field->setPosition($position);
        $field->setStep($step);

        return $field;
    }

    private function addFormTranslation(Form $form, string $locale, string $title, string $slug, ?string $description): void
    {
        $translation = new FormTranslation();
        $translation->setLocale($locale);
        $translation->setTitle($title);
        $translation->setSlug($slug);
        $translation->setDescription($description);
        $form->getTranslations()->add($translation);
    }

    /** @param list<string> $options */
    private function addFieldTranslation(
        FormField $field,
        string $locale,
        string $label,
        ?string $placeholder = null,
        array $options = [],
    ): void {
        $translation = new FormFieldTranslation();
        $translation->setLocale($locale);
        $translation->setLabel($label);
        $translation->setPlaceholder($placeholder);
        $translation->setOptions($options);
        $field->getTranslations()->set($locale, $translation);
    }
}
