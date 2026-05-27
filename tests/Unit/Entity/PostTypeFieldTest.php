<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\PostType\Entity\PostType;
use Aurora\Module\Editorial\PostType\Entity\PostTypeField;
use PHPUnit\Framework\TestCase;

final class PostTypeFieldTest extends TestCase
{
    public function testReferenceIsSupportedAsFieldType(): void
    {
        self::assertContains('reference', PostTypeField::TYPES);
    }

    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new PostTypeField())->getId());
    }

    public function testDefaultValues(): void
    {
        $field = new PostTypeField();

        self::assertSame('text', $field->getType());
        self::assertFalse($field->isRequired());
        self::assertFalse($field->isTranslatable());
        self::assertSame([], $field->getOptions());
        self::assertSame(0, $field->getPosition());
    }

    public function testNameAndLabelGettersAndSetters(): void
    {
        $field = (new PostTypeField())->setName('subtitle')->setLabel('Subtitle');

        self::assertSame('subtitle', $field->getName());
        self::assertSame('Subtitle', $field->getLabel());
    }

    public function testTypeGetterAndSetter(): void
    {
        $field = (new PostTypeField())->setType('textarea');

        self::assertSame('textarea', $field->getType());
    }

    public function testRequiredGetterAndSetter(): void
    {
        $field = (new PostTypeField())->setRequired(true);

        self::assertTrue($field->isRequired());
    }

    public function testTranslatableGetterAndSetter(): void
    {
        $field = (new PostTypeField())->setTranslatable(true);

        self::assertTrue($field->isTranslatable());
    }

    public function testOptionsGetterAndSetter(): void
    {
        $field = (new PostTypeField())->setOptions(['key' => 'value']);

        self::assertSame(['key' => 'value'], $field->getOptions());
    }

    public function testPositionGetterAndSetter(): void
    {
        $field = (new PostTypeField())->setPosition(3);

        self::assertSame(3, $field->getPosition());
    }

    public function testPostTypeGetterAndSetter(): void
    {
        $postType = new PostType();
        $field = (new PostTypeField())->setPostType($postType);

        self::assertSame($postType, $field->getPostType());
    }
}
