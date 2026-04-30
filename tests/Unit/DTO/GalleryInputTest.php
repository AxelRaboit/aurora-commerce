<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\DTO;

use Aurora\Module\Photo\Gallery\DTO\GalleryInput;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GalleryInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testFromArrayTrimsTitleAndSlug(): void
    {
        $input = GalleryInput::fromArray([
            'title' => '  Wedding 2026  ',
            'slug' => '  wedding-2026  ',
        ]);

        self::assertSame('Wedding 2026', $input->title);
        self::assertSame('wedding-2026', $input->slug);
    }

    public function testFromArrayParsesExpiresAt(): void
    {
        $input = GalleryInput::fromArray([
            'title' => 't',
            'slug' => 's',
            'expiresAt' => '2026-12-31T23:59:00+00:00',
        ]);

        self::assertInstanceOf(DateTimeImmutable::class, $input->expiresAt);
        self::assertSame('2026-12-31', $input->expiresAt->format('Y-m-d'));
    }

    public function testFromArrayInvalidExpiresAtBecomesNull(): void
    {
        $input = GalleryInput::fromArray([
            'title' => 't',
            'slug' => 's',
            'expiresAt' => 'not-a-date',
        ]);

        self::assertNull($input->expiresAt);
    }

    public function testFromArrayCoercesIds(): void
    {
        $input = GalleryInput::fromArray([
            'title' => 't',
            'slug' => 's',
            'coverMediaId' => '12',
            'clientContactId' => '7',
        ]);

        self::assertSame(12, $input->coverMediaId);
        self::assertSame(7, $input->clientContactId);
    }

    public function testFromArrayEmptyIdStringBecomesNull(): void
    {
        $input = GalleryInput::fromArray([
            'title' => 't',
            'slug' => 's',
            'coverMediaId' => '',
            'clientContactId' => '',
        ]);

        self::assertNull($input->coverMediaId);
        self::assertNull($input->clientContactId);
    }

    public function testFromArrayDefaultFlags(): void
    {
        $input = GalleryInput::fromArray(['title' => 't', 'slug' => 's']);

        self::assertTrue($input->allowOriginals);
        self::assertTrue($input->allowZipDownload);
        self::assertFalse($input->picksRequireIdentity);
        self::assertFalse($input->watermarkEnabled);
        self::assertFalse($input->clearPassword);
    }

    public function testValidationFailsForEmptyTitle(): void
    {
        $input = new GalleryInput(title: '', slug: 'valid');

        $violations = $this->validator->validate($input);

        self::assertGreaterThan(0, $violations->count());
    }

    public function testValidationFailsForInvalidSlugFormat(): void
    {
        $input = new GalleryInput(title: 't', slug: 'Has Spaces');

        $violations = $this->validator->validate($input);
        $hasSlugError = false;
        foreach ($violations as $violation) {
            if ('slug' === $violation->getPropertyPath()) {
                $hasSlugError = true;
            }
        }

        self::assertTrue($hasSlugError);
    }

    public function testValidationPassesForValidInput(): void
    {
        $input = new GalleryInput(title: 'Wedding', slug: 'wedding-2026');

        $violations = $this->validator->validate($input);

        self::assertCount(0, $violations);
    }

    public function testValidationRejectsTooLongWatermarkText(): void
    {
        $input = new GalleryInput(
            title: 't',
            slug: 's',
            watermarkText: str_repeat('a', 101),
        );

        $violations = $this->validator->validate($input);

        self::assertGreaterThan(0, $violations->count());
    }
}
