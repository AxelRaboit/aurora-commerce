<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Platform\User\Dto;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Platform\User\Dto\UpdateUserInput;
use PHPUnit\Framework\TestCase;

final class UpdateUserInputTest extends TestCase
{
    public function testFromArrayParsesFields(): void
    {
        $input = UpdateUserInput::fromArray([
            'name' => '  Jane  ',
            'email' => '  jane@example.com  ',
            'password' => 'new-secret',
            'locale' => 'en',
        ]);

        self::assertSame('Jane', $input->name);
        self::assertSame('jane@example.com', $input->email);
        self::assertSame('new-secret', $input->password);
        self::assertSame(LocaleEnum::English, $input->locale);
    }

    public function testFromArrayWithMissingPasswordReturnsEmpty(): void
    {
        $input = UpdateUserInput::fromArray(['name' => 'X', 'email' => 'x@x.com']);

        self::assertSame('', $input->password);
    }

    public function testFromArrayUsesDefaultLocale(): void
    {
        $input = UpdateUserInput::fromArray(['name' => 'X', 'email' => 'x@x.com']);

        self::assertSame(LocaleEnum::default(), $input->locale);
    }
}
