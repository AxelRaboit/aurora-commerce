<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Platform\Auth\Dto;

use Aurora\Module\Platform\Auth\Dto\AccessRequestInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AccessRequestInputTest extends TestCase
{
    public function testFromRequestParsesAllFields(): void
    {
        $request = new Request([], [
            'email' => '  jane@example.com  ',
            'name' => '  Jane  ',
            'message' => '  Please grant access  ',
        ]);

        $input = AccessRequestInput::fromRequest($request);

        self::assertSame('jane@example.com', $input->email);
        self::assertSame('Jane', $input->name);
        self::assertSame('Please grant access', $input->message);
    }

    public function testFromRequestEmptyFieldsBecomeNull(): void
    {
        $request = new Request([], [
            'email' => 'x@x.com',
            'name' => '   ',
            'message' => '',
        ]);

        $input = AccessRequestInput::fromRequest($request);

        self::assertNull($input->name);
        self::assertNull($input->message);
    }
}
