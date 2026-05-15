<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\User\Dto;

use Aurora\Core\User\Dto\UserInviteInput;
use PHPUnit\Framework\TestCase;

final class UserInviteInputTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $input = new UserInviteInput('Jane Doe', 'jane@example.com', 'editor', 'Welcome!');

        self::assertSame('Jane Doe', $input->getName());
        self::assertSame('jane@example.com', $input->getEmail());
        self::assertSame('editor', $input->getRole());
        self::assertSame('Welcome!', $input->getMessage());
    }

    public function testMessageIsNullByDefault(): void
    {
        $input = new UserInviteInput('Name', 'e@x.com', 'admin');

        self::assertNull($input->getMessage());
    }
}
