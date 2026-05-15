<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Form\Dto;

use Aurora\Module\Editorial\Form\Dto\ReorderFieldsInput;
use PHPUnit\Framework\TestCase;

final class ReorderFieldsInputTest extends TestCase
{
    public function testFromArrayWithValidIds(): void
    {
        $input = ReorderFieldsInput::fromArray(['orderedIds' => [3, 1, 2]]);

        self::assertSame([3, 1, 2], $input->orderedIds);
    }

    public function testFromArrayFiltersNonPositiveAndCoercesStrings(): void
    {
        $input = ReorderFieldsInput::fromArray(['orderedIds' => [1, '2', '', 0, -1, 3]]);

        self::assertSame([1, 2, 3], $input->orderedIds);
    }

    public function testFromArrayWithMissingKeyReturnsEmpty(): void
    {
        $input = ReorderFieldsInput::fromArray([]);

        self::assertSame([], $input->orderedIds);
    }

    public function testDefaultConstructorReturnsEmptyList(): void
    {
        self::assertSame([], (new ReorderFieldsInput())->orderedIds);
    }
}
