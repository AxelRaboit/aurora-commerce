<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\DTO;

use Aurora\Module\Photo\Gallery\DTO\GalleryItemAddInput;
use Aurora\Module\Photo\Gallery\DTO\GalleryItemBulkDeleteInput;
use Aurora\Module\Photo\Gallery\DTO\GalleryItemCaptionInput;
use Aurora\Module\Photo\Gallery\DTO\GalleryItemReorderInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GalleryItemInputsTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testAddInputCoercesAndFiltersIds(): void
    {
        $input = GalleryItemAddInput::fromArray(['mediaIds' => ['1', '2', '0', '-3', 'abc', '4']]);

        self::assertSame([1, 2, 4], $input->mediaIds);
    }

    public function testAddInputRejectsEmptyList(): void
    {
        $input = GalleryItemAddInput::fromArray(['mediaIds' => []]);

        self::assertGreaterThan(0, $this->validator->validate($input)->count());
    }

    public function testReorderInputCoercesIds(): void
    {
        $input = GalleryItemReorderInput::fromArray(['itemIds' => [3, '5', 7]]);

        self::assertSame([3, 5, 7], $input->itemIds);
    }

    public function testCaptionInputTrimsAndNullsEmpty(): void
    {
        self::assertNull(GalleryItemCaptionInput::fromArray(['caption' => '   '])->caption);
        self::assertSame('Kiss on the beach', GalleryItemCaptionInput::fromArray(['caption' => '  Kiss on the beach  '])->caption);
    }

    public function testCaptionInputRejectsTooLong(): void
    {
        $input = new GalleryItemCaptionInput(caption: str_repeat('a', GalleryItemCaptionInput::MAX_LENGTH + 1));

        self::assertGreaterThan(0, $this->validator->validate($input)->count());
    }

    public function testCaptionInputAcceptsMaxLength(): void
    {
        $input = new GalleryItemCaptionInput(caption: str_repeat('a', GalleryItemCaptionInput::MAX_LENGTH));

        self::assertCount(0, $this->validator->validate($input));
    }

    public function testBulkDeleteInputRejectsEmptyList(): void
    {
        $input = GalleryItemBulkDeleteInput::fromArray(['itemIds' => []]);

        self::assertGreaterThan(0, $this->validator->validate($input)->count());
    }
}
