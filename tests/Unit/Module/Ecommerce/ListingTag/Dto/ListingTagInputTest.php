<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Ecommerce\ListingTag\Dto;

use Aurora\Module\Ecommerce\ListingTag\Dto\ListingTagInput;
use Aurora\Module\Ecommerce\ListingTag\Dto\ListingTagTranslationInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ListingTagInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /** @return list<string> */
    private function messages(ListingTagInput $input): array
    {
        $violations = $this->validator->validate($input);
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = (string) $violation->getMessage();
        }

        return $messages;
    }

    public function testValidInputPasses(): void
    {
        $input = new ListingTagInput(
            color: '#6366F1',
            isVisible: true,
            translations: [
                'en' => new ListingTagTranslationInput('Promo', 'promo', null),
            ],
        );

        self::assertSame([], $this->messages($input));
    }

    public function testEmptyTranslationsFails(): void
    {
        $input = new ListingTagInput(
            color: '#6366F1',
            isVisible: true,
            translations: [],
        );

        self::assertContains('ecommerce.listing_tags.errors.translations_required', $this->messages($input));
    }

    public function testAllEmptyTranslationNamesFails(): void
    {
        $input = new ListingTagInput(
            color: '#6366F1',
            isVisible: true,
            translations: [
                'en' => new ListingTagTranslationInput('', null, null),
                'fr' => new ListingTagTranslationInput('', null, null),
            ],
        );

        $messages = $this->messages($input);
        self::assertContains('ecommerce.listing_tags.errors.name_required', $messages);
        self::assertContains('ecommerce.listing_tags.errors.translations_required', $messages);
    }

    public function testInvalidHexColorFails(): void
    {
        $input = new ListingTagInput(
            color: 'not-a-hex',
            isVisible: true,
            translations: [
                'en' => new ListingTagTranslationInput('Promo', 'promo', null),
            ],
        );

        self::assertContains('ecommerce.listing_tags.errors.color_invalid', $this->messages($input));
    }
}
