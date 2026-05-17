<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Platform\User\Dto;

use Aurora\Core\Platform\User\Dto\UserInviteInputFactory;
use PHPUnit\Framework\TestCase;

final class UserInviteInputFactoryTest extends TestCase
{
    public function testFromArrayParsesAllFields(): void
    {
        $input = (new UserInviteInputFactory())->fromArray([
            'name' => '  Jane  ',
            'email' => '  jane@example.com  ',
            'role' => '  editor  ',
            'message' => '  Welcome!  ',
        ]);

        self::assertSame('Jane', $input->getName());
        self::assertSame('jane@example.com', $input->getEmail());
        self::assertSame('editor', $input->getRole());
        self::assertSame('Welcome!', $input->getMessage());
    }

    public function testFromArrayWithDefaults(): void
    {
        $input = (new UserInviteInputFactory())->fromArray([]);

        self::assertSame('', $input->getName());
        self::assertSame('', $input->getEmail());
        self::assertSame('', $input->getRole());
        self::assertNull($input->getMessage());
    }
}
