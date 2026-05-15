<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Module\Editorial\Form\Entity\FormInterface;
use Aurora\Module\Editorial\Form\Entity\FormTranslation;
use PHPUnit\Framework\TestCase;

final class FormTranslationTest extends TestCase
{
    public function testIdIsNullByDefault(): void
    {
        self::assertNull((new FormTranslation())->getId());
    }

    public function testDescriptionIsNullByDefault(): void
    {
        self::assertNull((new FormTranslation())->getDescription());
    }

    public function testFormGetterAndSetter(): void
    {
        $form = $this->createStub(FormInterface::class);
        $translation = (new FormTranslation())->setForm($form);

        self::assertSame($form, $translation->getForm());
    }

    public function testLocaleGetterAndSetter(): void
    {
        $translation = (new FormTranslation())->setLocale('fr');

        self::assertSame('fr', $translation->getLocale());
    }

    public function testTitleAndSlugGettersAndSetters(): void
    {
        $translation = (new FormTranslation())->setTitle('Contact')->setSlug('contact');

        self::assertSame('Contact', $translation->getTitle());
        self::assertSame('contact', $translation->getSlug());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $translation = (new FormTranslation())->setDescription('Contact form');

        self::assertSame('Contact form', $translation->getDescription());

        $translation->setDescription(null);
        self::assertNull($translation->getDescription());
    }
}
