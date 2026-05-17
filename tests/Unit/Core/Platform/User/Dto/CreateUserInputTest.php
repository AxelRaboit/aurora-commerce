<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Platform\User\Dto;

use Aurora\Core\Locale\Enum\LocaleEnum;
use Aurora\Core\Platform\User\Dto\CreateUserInput;
use PHPUnit\Framework\TestCase;

final class CreateUserInputTest extends TestCase
{
    public function testFromArrayParsesFields(): void
    {
        $input = CreateUserInput::fromArray([
            'name' => '  Jane Doe  ',
            'email' => '  jane@example.com  ',
            'password' => 'secret',
            'locale' => 'en',
        ]);

        self::assertSame('Jane Doe', $input->name);
        self::assertSame('jane@example.com', $input->email);
        self::assertSame('secret', $input->password);
        self::assertSame(LocaleEnum::English, $input->locale);
    }

    public function testFromArrayUsesDefaultLocale(): void
    {
        $input = CreateUserInput::fromArray(['name' => 'X', 'email' => 'x@x.com', 'password' => 'p']);

        self::assertSame(LocaleEnum::default(), $input->locale);
    }

    public function testFromArrayWithInvalidLocaleUsesDefault(): void
    {
        $input = CreateUserInput::fromArray([
            'name' => 'X',
            'email' => 'x@x.com',
            'password' => 'p',
            'locale' => 'invalid',
        ]);

        self::assertSame(LocaleEnum::default(), $input->locale);
    }
}
