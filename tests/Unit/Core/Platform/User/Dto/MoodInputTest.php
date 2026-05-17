<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Platform\User\Dto;

use Aurora\Core\Platform\User\Dto\MoodInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class MoodInputTest extends TestCase
{
    public function testFromRequestParsesMoodMessage(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [], json_encode(['moodMessage' => '  Hello world  ']));

        $input = MoodInput::fromRequest($request);

        self::assertSame('Hello world', $input->moodMessage);
    }

    public function testFromRequestWithEmptyContent(): void
    {
        $request = Request::create('/', 'POST');

        $input = MoodInput::fromRequest($request);

        self::assertNull($input->moodMessage);
    }

    public function testFromRequestWithMissingField(): void
    {
        $request = Request::create('/', 'POST', [], [], [], [], json_encode([]));

        $input = MoodInput::fromRequest($request);

        self::assertNull($input->moodMessage);
    }

    public function testConstructorDefaults(): void
    {
        self::assertNull((new MoodInput())->moodMessage);
    }
}
