<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Dto;

use Aurora\Module\Photo\Gallery\Dto\GalleryInviteInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GalleryInviteInputTest extends TestCase
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
        $input = GalleryInviteInput::fromArray([
            'name' => "  John Smith\t",
            'email' => '  JOHN@Example.COM  ',
        ]);

        self::assertSame('John Smith', $input->name);
        self::assertSame('john@example.com', $input->email);
    }

    public function testFromArrayDefaultsToEmptyStrings(): void
    {
        $input = GalleryInviteInput::fromArray([]);

        self::assertSame('', $input->name);
        self::assertSame('', $input->email);
    }

    public function testValidationFailsForBlankName(): void
    {
        $input = new GalleryInviteInput(name: '', email: 'john@example.com');

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
        $input = new GalleryInviteInput(name: 'John', email: '');

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
        $input = new GalleryInviteInput(name: 'John', email: 'nope');

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
        $input = new GalleryInviteInput(name: 'John', email: 'john@example.com');

        $violations = $this->validator->validate($input);

        self::assertCount(0, $violations);
    }
}
