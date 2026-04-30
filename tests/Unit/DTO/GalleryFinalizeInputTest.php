<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\DTO;

use Aurora\Module\Photo\Gallery\DTO\GalleryFinalizeInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GalleryFinalizeInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testFromArrayTrimsNameAndLowercasesEmail(): void
    {
        $input = GalleryFinalizeInput::fromArray([
            'name' => '  Jane Doe  ',
            'email' => '  JANE@Example.COM  ',
        ]);

        self::assertSame('Jane Doe', $input->name);
        self::assertSame('jane@example.com', $input->email);
    }

    public function testFromArrayDefaultsToEmptyStrings(): void
    {
        $input = GalleryFinalizeInput::fromArray([]);

        self::assertSame('', $input->name);
        self::assertSame('', $input->email);
    }

    public function testValidationFailsForBlankName(): void
    {
        $input = new GalleryFinalizeInput(name: '', email: 'jane@example.com');

        $violations = $this->validator->validate($input);
        $messages = [];
        foreach ($violations as $violation) {
            if ('name' === $violation->getPropertyPath()) {
                $messages[] = $violation->getMessage();
            }
        }

        self::assertContains('photo.galleries.errors.name_required', $messages);
    }

    public function testValidationFailsForBlankEmail(): void
    {
        $input = new GalleryFinalizeInput(name: 'Jane', email: '');

        $violations = $this->validator->validate($input);
        $messages = [];
        foreach ($violations as $violation) {
            if ('email' === $violation->getPropertyPath()) {
                $messages[] = $violation->getMessage();
            }
        }

        self::assertContains('photo.galleries.errors.email_required', $messages);
    }

    public function testValidationFailsForInvalidEmail(): void
    {
        $input = new GalleryFinalizeInput(name: 'Jane', email: 'not-an-email');

        $violations = $this->validator->validate($input);
        $messages = [];
        foreach ($violations as $violation) {
            if ('email' === $violation->getPropertyPath()) {
                $messages[] = $violation->getMessage();
            }
        }

        self::assertContains('photo.galleries.errors.email_invalid', $messages);
    }

    public function testValidationPassesForValidInput(): void
    {
        $input = new GalleryFinalizeInput(name: 'Jane', email: 'jane@example.com');

        $violations = $this->validator->validate($input);

        self::assertCount(0, $violations);
    }
}
