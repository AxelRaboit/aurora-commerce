<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Form\Enum;

use Aurora\Module\Editorial\Form\Enum\FormFieldTypeEnum;
use PHPUnit\Framework\TestCase;

final class FormFieldTypeEnumTest extends TestCase
{
    public function testGetLabelKeyPrefixesCaseValue(): void
    {
        self::assertSame('backend.editorial.forms.field_type.text', FormFieldTypeEnum::Text->getLabelKey());
        self::assertSame('backend.editorial.forms.field_type.email', FormFieldTypeEnum::Email->getLabelKey());
    }

    public function testHasOptionsReturnsTrueOnlyForOptionTypes(): void
    {
        self::assertTrue(FormFieldTypeEnum::Select->hasOptions());
        self::assertTrue(FormFieldTypeEnum::Checkbox->hasOptions());
        self::assertTrue(FormFieldTypeEnum::Radio->hasOptions());

        self::assertFalse(FormFieldTypeEnum::Text->hasOptions());
        self::assertFalse(FormFieldTypeEnum::Email->hasOptions());
        self::assertFalse(FormFieldTypeEnum::Textarea->hasOptions());
        self::assertFalse(FormFieldTypeEnum::Number->hasOptions());
        self::assertFalse(FormFieldTypeEnum::Date->hasOptions());
        self::assertFalse(FormFieldTypeEnum::Tel->hasOptions());
    }

    public function testCasesHaveExpectedValues(): void
    {
        self::assertSame('text', FormFieldTypeEnum::Text->value);
        self::assertSame('select', FormFieldTypeEnum::Select->value);
        self::assertSame('tel', FormFieldTypeEnum::Tel->value);
    }
}
