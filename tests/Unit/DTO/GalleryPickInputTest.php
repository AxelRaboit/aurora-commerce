<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Dto;

use Aurora\Module\Photo\Gallery\Dto\GalleryPickInput;
use Aurora\Module\Photo\Gallery\Enum\PickKindEnum;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GalleryPickInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testFromArrayParsesKindEnum(): void
    {
        $input = GalleryPickInput::fromArray(['kind' => 'print']);

        self::assertSame(PickKindEnum::Print, $input->kind);
    }

    public function testFromArrayDefaultsToFavoriteKindWhenInvalid(): void
    {
        $input = GalleryPickInput::fromArray(['kind' => 'not-a-real-kind']);

        self::assertSame(PickKindEnum::Favorite, $input->kind);

        $missing = GalleryPickInput::fromArray([]);
        self::assertSame(PickKindEnum::Favorite, $missing->kind);
    }

    public function testFromArrayLowercasesEmail(): void
    {
        $input = GalleryPickInput::fromArray([
            'name' => '  Jane  ',
            'email' => '  JANE@Example.COM  ',
        ]);

        self::assertSame('Jane', $input->visitorName);
        self::assertSame('jane@example.com', $input->visitorEmail);
    }

    public function testFromArrayWithMissingNameAndEmailReturnsNulls(): void
    {
        $input = GalleryPickInput::fromArray([]);

        self::assertNull($input->visitorName);
        self::assertNull($input->visitorEmail);
    }

    public function testValidatorAcceptsNullEmail(): void
    {
        $input = new GalleryPickInput(visitorName: 'Jane', visitorEmail: null);

        $violations = $this->validator->validate($input);

        self::assertCount(0, $violations);
    }

    public function testValidatorRejectsInvalidEmail(): void
    {
        $input = new GalleryPickInput(visitorEmail: 'not-an-email');

        $violations = $this->validator->validate($input);
        $messages = [];
        foreach ($violations as $violation) {
            if ('visitorEmail' === $violation->getPropertyPath()) {
                $messages[] = $violation->getMessage();
            }
        }

        self::assertContains('photo.galleries.errors.email_invalid', $messages);
    }
}
