<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Form\Entity\FormField;
use Aurora\Module\Editorial\Form\Entity\FormFieldTranslation;
use PHPUnit\Framework\TestCase;

final class FormFieldTranslationTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new FormFieldTranslation())->getId());
    }

    public function testPlaceholderIsNullByDefault(): void
    {
        self::assertNull((new FormFieldTranslation())->getPlaceholder());
    }

    public function testOptionsDefaultsToEmptyArray(): void
    {
        self::assertSame([], (new FormFieldTranslation())->getOptions());
    }

    public function testLocaleGetterAndSetter(): void
    {
        $translation = (new FormFieldTranslation())->setLocale('en');

        self::assertSame('en', $translation->getLocale());
    }

    public function testLabelGetterAndSetter(): void
    {
        $translation = (new FormFieldTranslation())->setLabel('Full name');

        self::assertSame('Full name', $translation->getLabel());
    }

    public function testPlaceholderGetterAndSetter(): void
    {
        $translation = (new FormFieldTranslation())->setPlaceholder('e.g. John Doe');

        self::assertSame('e.g. John Doe', $translation->getPlaceholder());

        $translation->setPlaceholder(null);
        self::assertNull($translation->getPlaceholder());
    }

    public function testOptionsGetterAndSetter(): void
    {
        $translation = (new FormFieldTranslation())->setOptions(['Option A', 'Option B']);

        self::assertSame(['Option A', 'Option B'], $translation->getOptions());
    }

    public function testFieldGetterAndSetter(): void
    {
        $field = new FormField();
        $translation = (new FormFieldTranslation())->setField($field);

        self::assertSame($field, $translation->getField());
    }

    public function testSettersReturnSelf(): void
    {
        $translation = new FormFieldTranslation();

        self::assertSame($translation, $translation->setField(new FormField()));
        self::assertSame($translation, $translation->setLocale('fr'));
        self::assertSame($translation, $translation->setLabel('l'));
        self::assertSame($translation, $translation->setPlaceholder('p'));
        self::assertSame($translation, $translation->setOptions(['a']));
    }
}
