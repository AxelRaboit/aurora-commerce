<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslation;
use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use PHPUnit\Framework\TestCase;

final class FormFieldTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new FormField())->getId());
    }

    public function testDefaultValues(): void
    {
        $field = new FormField();

        self::assertNull($field->getReference());
        self::assertFalse($field->isRequired());
        self::assertSame(0, $field->getPosition());
        self::assertNull($field->getConditions());
        self::assertSame('and', $field->getConditionsLogic());
        self::assertNull($field->getStep());
    }

    public function testTranslationsCollectionInitialized(): void
    {
        self::assertCount(0, (new FormField())->getTranslations());
    }

    public function testTypeGetterAndSetter(): void
    {
        $field = (new FormField())->setType(FormFieldTypeEnum::Text);

        self::assertSame(FormFieldTypeEnum::Text, $field->getType());
    }

    public function testFormGetterAndSetter(): void
    {
        $form = $this->createStub(FormInterface::class);
        $field = (new FormField())->setForm($form);

        self::assertSame($form, $field->getForm());
    }

    public function testAddTranslationIndexedByLocale(): void
    {
        $field = new FormField();
        $translation = (new FormFieldTranslation())->setLocale('fr')->setLabel('Nom');

        $field->addTranslation($translation);

        self::assertCount(1, $field->getTranslations());
        self::assertSame($translation, $field->getTranslation('fr'));
        self::assertSame($field, $translation->getField());
    }

    public function testAddTranslationIgnoresDuplicateLocale(): void
    {
        $field = new FormField();
        $first  = (new FormFieldTranslation())->setLocale('fr')->setLabel('Nom');
        $second = (new FormFieldTranslation())->setLocale('fr')->setLabel('Prénom');

        $field->addTranslation($first);
        $field->addTranslation($second);

        self::assertCount(1, $field->getTranslations());
        self::assertSame($first, $field->getTranslation('fr'));
    }

    public function testRemoveTranslation(): void
    {
        $field = new FormField();
        $translation = (new FormFieldTranslation())->setLocale('fr')->setLabel('Nom');

        $field->addTranslation($translation);
        $field->removeTranslation($translation);

        self::assertCount(0, $field->getTranslations());
        self::assertNull($field->getTranslation('fr'));
    }

    public function testGetTranslationReturnsNullForMissingLocale(): void
    {
        self::assertNull((new FormField())->getTranslation('de'));
    }
}
