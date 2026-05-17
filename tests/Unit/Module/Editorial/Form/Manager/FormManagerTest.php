<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Form\Manager;

use Aurora\Core\Locale\Service\TranslationLocaleSyncerInterface;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Form\Dto\FormFieldInput;
use Aurora\Module\Editorial\Form\Dto\FormInput;
use Aurora\Module\Editorial\Form\Entity\AbstractForm;
use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use Aurora\Module\Editorial\Form\Manager\FormManager;
use Aurora\Module\Editorial\Form\Repository\FormTranslationRepository;
use Aurora\Module\Editorial\Form\Service\FormNotificationService;
use Aurora\Module\Editorial\Form\Service\FormWebhookService;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class FormManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private FormTranslationRepository $translationRepository;
    private FormNotificationService $notificationService;
    private FormWebhookService $webhookService;
    private SettingRepository $settingRepository;
    private EventDispatcherInterface $eventDispatcher;
    private TranslationLocaleSyncerInterface $translationSyncer;
    private FormManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->translationRepository = $this->createMock(FormTranslationRepository::class);
        $this->notificationService = $this->createMock(FormNotificationService::class);
        $this->webhookService = $this->createMock(FormWebhookService::class);
        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->settingRepository->method('getOrDefault')->willReturn('FRM');
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->translationSyncer = $this->createMock(TranslationLocaleSyncerInterface::class);
        $this->translationSyncer->method('stale')->willReturn([]);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('translated');

        $result = $this->createStub(Result::class);
        $result->method('fetchOne')->willReturn(1);
        $connection = $this->createStub(Connection::class);
        $connection->method('executeQuery')->willReturn($result);

        $this->manager = new FormManager(
            $this->entityManager,
            $this->translationRepository,
            $translator,
            $this->notificationService,
            $this->webhookService,
            new SequenceGenerator($connection),
            $this->settingRepository,
            $this->eventDispatcher,
            $this->translationSyncer,
        );
    }

    public function testCreateAssignsReferenceAndPersists(): void
    {
        $this->translationRepository->method('findOneByLocaleAndSlug')->willReturn(null);
        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $form = $this->manager->create($this->makeInput(
            translations: ['fr' => ['title' => 'Contact', 'slug' => 'contact', 'description' => 'D']],
        ));

        self::assertSame('FRM-000001', $form->getReference());
        self::assertSame('Contact', $form->getTranslation('fr')->getTitle());
        self::assertSame('contact', $form->getTranslation('fr')->getSlug());
    }

    public function testCreateApplyInputCopiesTopLevelFields(): void
    {
        $this->translationRepository->method('findOneByLocaleAndSlug')->willReturn(null);

        $form = $this->manager->create($this->makeInput(
            notifyEmail: 'ops@example.com',
            webhookUrl: 'https://hooks.example.com/x',
            crmSync: true,
            steps: ['s1', 's2'],
            active: false,
            translations: ['fr' => ['title' => 'T', 'slug' => 't', 'description' => null]],
        ));

        self::assertSame('ops@example.com', $form->getNotifyEmail());
        self::assertSame('https://hooks.example.com/x', $form->getWebhookUrl());
        self::assertTrue($form->isCrmSync());
        self::assertSame(['s1', 's2'], $form->getSteps());
        self::assertFalse($form->isActive());
    }

    public function testCreateRejectsInvalidSlugFormat(): void
    {
        $this->translationRepository->method('findOneByLocaleAndSlug')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);

        $this->manager->create($this->makeInput(
            translations: ['fr' => ['title' => 'T', 'slug' => 'invalid SLUG!', 'description' => null]],
        ));
    }

    public function testCreateRejectsDuplicateSlugWithinSameLocale(): void
    {
        // Slugs are unique per locale (one URL per language). A second
        // form with the same slug in the same locale would collide on
        // the public route.
        $existing = $this->makeTranslation('contact');
        $this->translationRepository->method('findOneByLocaleAndSlug')->willReturn($existing);

        $this->expectException(InvalidArgumentException::class);

        $this->manager->create($this->makeInput(
            translations: ['fr' => ['title' => 'T', 'slug' => 'contact', 'description' => null]],
        ));
    }

    public function testUpdateExcludesCurrentFormFromSlugUniqueness(): void
    {
        // When updating, the slug-uniqueness check must exclude the
        // current form id — otherwise saving without changing the slug
        // would self-collide.
        $form = $this->makeForm(id: 7);
        $this->translationRepository->expects(self::once())
            ->method('findOneByLocaleAndSlugExcluding')
            ->with('fr', 'contact', 7)
            ->willReturn(null);
        // The non-excluding variant must NEVER be called on update.
        $this->translationRepository->expects(self::never())->method('findOneByLocaleAndSlug');

        $this->manager->update($form, $this->makeInput(
            translations: ['fr' => ['title' => 'T', 'slug' => 'contact', 'description' => null]],
        ));
    }

    public function testUpdateBumpsUpdatedAt(): void
    {
        $form = $this->makeForm();
        $oldUpdatedAt = $form->getUpdatedAt();
        $this->translationRepository->method('findOneByLocaleAndSlugExcluding')->willReturn(null);

        $this->manager->update($form, $this->makeInput(
            translations: ['fr' => ['title' => 'T', 'slug' => 't', 'description' => null]],
        ));

        self::assertGreaterThanOrEqual($oldUpdatedAt, $form->getUpdatedAt());
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $form = $this->makeForm();

        $this->entityManager->expects(self::once())->method('remove')->with($form);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->delete($form);
    }

    public function testCreateFieldAssignsPositionFromExistingCount(): void
    {
        // Field position = current count (i.e. appended at the end of
        // the field list).
        $form = $this->makeForm();
        $existing = $this->makeField(id: 1, position: 5);
        $form->getFields()->add($existing);

        $field = $this->manager->createField($form, $this->makeFieldInput());

        self::assertSame(1, $field->getPosition(), 'position = count(before)=1 (max+1 not used here, simple append)');
        self::assertSame('FRM-000001', $field->getReference());
    }

    public function testUpdateFieldPreservesPosition(): void
    {
        // applyFieldInput on update passes the field's current position,
        // not 0 — so editing the field doesn't accidentally move it to
        // the top of the form.
        $field = $this->makeField(id: 1, position: 3);
        $field->setForm($this->makeForm());

        $this->manager->updateField($field, $this->makeFieldInput());

        self::assertSame(3, $field->getPosition());
    }

    public function testDeleteFieldRemovesFromFormAndDeletesEntity(): void
    {
        $form = $this->makeForm();
        $field = $this->makeField(id: 1);
        $field->setForm($form);
        $form->addField($field);

        $this->entityManager->expects(self::once())->method('remove')->with($field);
        $this->entityManager->expects(self::atLeastOnce())->method('flush');

        $this->manager->deleteField($field);

        self::assertFalse($form->getFields()->contains($field));
    }

    public function testReorderFieldsRewritesPositionsFromIdOrder(): void
    {
        // Front ships the new ordered ids; manager rewrites each
        // field's position to its index in that list.
        $form = $this->makeForm();
        $a = $this->makeField(id: 10);
        $b = $this->makeField(id: 20);
        $c = $this->makeField(id: 30);
        $form->getFields()->add($a);
        $form->getFields()->add($b);
        $form->getFields()->add($c);

        $this->manager->reorderFields($form, [30, 10, 20]);

        self::assertSame(0, $c->getPosition());
        self::assertSame(1, $a->getPosition());
        self::assertSame(2, $b->getPosition());
    }

    public function testFindActiveTranslationReturnsNullWhenFormInactive(): void
    {
        // Public frontend only serves active forms. An inactive form is
        // hidden as if it didn't exist (returns null, not the entity
        // with an "inactive" flag — defense-in-depth against accidental
        // exposure).
        $form = $this->makeForm();
        $form->setActive(false);
        $translation = $this->makeTranslation('contact');
        (new ReflectionProperty(FormTranslation::class, 'form'))->setValue($translation, $form);

        $this->translationRepository->method('findOneByLocaleAndSlug')->willReturn($translation);

        self::assertNull($this->manager->findActiveTranslation('fr', 'contact'));
    }

    public function testFindActiveTranslationReturnsTranslationWhenFormActive(): void
    {
        $form = $this->makeForm();
        $form->setActive(true);
        $translation = $this->makeTranslation('contact');
        (new ReflectionProperty(FormTranslation::class, 'form'))->setValue($translation, $form);

        $this->translationRepository->method('findOneByLocaleAndSlug')->willReturn($translation);

        self::assertSame($translation, $this->manager->findActiveTranslation('fr', 'contact'));
    }

    public function testSubmitPersistsAndDispatchesNotificationsWebhookAndEvent(): void
    {
        // Submission flow has 5 side effects: persist + flush + notify
        // admin + notify author + webhook + dispatch event. All must
        // fire in the same call.
        $form = $this->makeForm();

        $this->entityManager->expects(self::atLeastOnce())->method('persist');
        $this->entityManager->expects(self::atLeastOnce())->method('flush');
        $this->notificationService->expects(self::once())->method('notifyAdmin')->with($form, self::anything(), 'fr');
        $this->notificationService->expects(self::once())->method('notifyAuthorIfPresent')->with($form, self::anything(), 'fr');
        $this->webhookService->expects(self::once())->method('send')->with($form, self::anything(), 'fr');
        $this->eventDispatcher->expects(self::once())->method('dispatch');

        $submission = $this->manager->submit($form, ['name' => 'Alice'], 'fr', '1.2.3.4');

        self::assertSame(['name' => 'Alice'], $submission->getData());
        self::assertSame('fr', $submission->getLocale());
        self::assertSame('1.2.3.4', $submission->getIp());
        self::assertSame('FRM-000001', $submission->getReference());
    }

    // ── Fixtures ────────────────────────────────────────────────────

    /** @param array<string, mixed> $translations */
    private function makeInput(
        ?string $notifyEmail = null,
        ?string $webhookUrl = null,
        bool $crmSync = false,
        ?array $steps = null,
        bool $active = true,
        array $translations = [],
    ): FormInput {
        return new FormInput(
            notifyEmail: $notifyEmail,
            webhookUrl: $webhookUrl,
            crmSync: $crmSync,
            steps: $steps,
            active: $active,
            translations: $translations,
        );
    }

    private function makeFieldInput(): FormFieldInput
    {
        return new FormFieldInput(
            type: 'text',
            required: false,
            step: null,
            conditions: null,
            conditionsLogic: 'and',
            translations: ['fr' => ['label' => 'L', 'placeholder' => null, 'options' => []]],
        );
    }

    private function makeForm(int $id = 1): Form
    {
        $form = new Form();
        (new ReflectionProperty(Form::class, 'id'))->setValue($form, $id);
        $form->setActive(true);

        $now = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
        (new ReflectionProperty(AbstractForm::class, 'createdAt'))->setValue($form, $now);
        (new ReflectionProperty(AbstractForm::class, 'updatedAt'))->setValue($form, $now);

        return $form;
    }

    private function makeField(int $id, int $position = 0): FormField
    {
        $field = new FormField();
        (new ReflectionProperty(FormField::class, 'id'))->setValue($field, $id);
        $field->setType(FormFieldTypeEnum::Text);
        $field->setRequired(false);
        $field->setPosition($position);

        return $field;
    }

    private function makeTranslation(string $slug): FormTranslation
    {
        $translation = new FormTranslation();
        $translation->setLocale('fr');
        $translation->setTitle('T');
        $translation->setSlug($slug);

        return $translation;
    }
}
