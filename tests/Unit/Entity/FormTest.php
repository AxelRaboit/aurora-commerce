<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Form\Entity\Form;
use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FormTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new Form())->getId());
    }

    public function testDefaultValues(): void
    {
        $form = new Form();

        self::assertNull($form->getReference());
        self::assertNull($form->getNotifyEmail());
        self::assertNull($form->getWebhookUrl());
        self::assertFalse($form->isCrmSync());
        self::assertNull($form->getSteps());
        self::assertTrue($form->isActive());
    }

    public function testCollectionsInitialized(): void
    {
        $form = new Form();

        self::assertCount(0, $form->getTranslations());
        self::assertCount(0, $form->getFields());
        self::assertCount(0, $form->getSubmissions());
    }

    public function testTimestampsInitialized(): void
    {
        $form = new Form();

        self::assertInstanceOf(DateTimeImmutable::class, $form->getCreatedAt());
        self::assertInstanceOf(DateTimeImmutable::class, $form->getUpdatedAt());
    }

    public function testNotifyEmailAndWebhookUrl(): void
    {
        $form = (new Form())
            ->setNotifyEmail('admin@example.com')
            ->setWebhookUrl('https://example.com/hook');

        self::assertSame('admin@example.com', $form->getNotifyEmail());
        self::assertSame('https://example.com/hook', $form->getWebhookUrl());
    }

    public function testCrmSyncAndActiveFlags(): void
    {
        $form = (new Form())->setCrmSync(true)->setActive(false);

        self::assertTrue($form->isCrmSync());
        self::assertFalse($form->isActive());
    }

    public function testStepsGetterAndSetter(): void
    {
        $steps = [['title' => 'Step 1'], ['title' => 'Step 2']];
        $form = (new Form())->setSteps($steps);

        self::assertSame($steps, $form->getSteps());

        $form->setSteps(null);
        self::assertNull($form->getSteps());
    }

    public function testReferenceGetterAndSetter(): void
    {
        $form = (new Form())->setReference('FORM-001');

        self::assertSame('FORM-001', $form->getReference());

        $form->setReference(null);
        self::assertNull($form->getReference());
    }

    public function testAddTranslationIndexedByLocale(): void
    {
        $form = new Form();
        $translation = (new FormTranslation())->setLocale('fr')->setTitle('Contact');

        $form->addTranslation($translation);

        self::assertCount(1, $form->getTranslations());
        self::assertSame($translation, $form->getTranslation('fr'));
        self::assertSame($form, $translation->getForm());
    }

    public function testRemoveTranslation(): void
    {
        $form = new Form();
        $translation = (new FormTranslation())->setLocale('fr')->setTitle('Contact');

        $form->addTranslation($translation);
        $form->removeTranslation($translation);

        self::assertCount(0, $form->getTranslations());
    }

    public function testGetTranslationReturnsNullForMissingLocale(): void
    {
        self::assertNull((new Form())->getTranslation('de'));
    }

    public function testAddAndRemoveField(): void
    {
        $form = new Form();
        $field = new FormField();

        $form->addField($field);
        self::assertCount(1, $form->getFields());

        $form->addField($field);
        self::assertCount(1, $form->getFields(), 'duplicate is ignored');

        $form->removeField($field);
        self::assertCount(0, $form->getFields());
    }

    public function testFindFieldByIdReturnsNullForEmpty(): void
    {
        self::assertNull((new Form())->findFieldById(42));
    }

    public function testUpdatedAtSetter(): void
    {
        $date = new DateTimeImmutable('2026-02-01');
        $form = (new Form())->setUpdatedAt($date);

        self::assertSame($date, $form->getUpdatedAt());
    }
}
