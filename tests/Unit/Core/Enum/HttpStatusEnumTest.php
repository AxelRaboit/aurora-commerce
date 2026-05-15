<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Enum;

use Aurora\Core\Enum\HttpStatusEnum;
use PHPUnit\Framework\TestCase;

final class HttpStatusEnumTest extends TestCase
{
    public function testSuccessStatusCodes(): void
    {
        self::assertSame(200, HttpStatusEnum::Ok->value);
        self::assertSame(201, HttpStatusEnum::Created->value);
        self::assertSame(204, HttpStatusEnum::NoContent->value);
    }

    public function testClientErrorStatusCodes(): void
    {
        self::assertSame(400, HttpStatusEnum::BadRequest->value);
        self::assertSame(401, HttpStatusEnum::Unauthorized->value);
        self::assertSame(403, HttpStatusEnum::Forbidden->value);
        self::assertSame(404, HttpStatusEnum::NotFound->value);
        self::assertSame(409, HttpStatusEnum::Conflict->value);
        self::assertSame(422, HttpStatusEnum::UnprocessableEntity->value);
    }

    public function testRedirectionStatusCodes(): void
    {
        self::assertSame(301, HttpStatusEnum::MovedPermanently->value);
        self::assertSame(302, HttpStatusEnum::Found->value);
    }

    public function testServerErrorStatusCodes(): void
    {
        self::assertSame(500, HttpStatusEnum::InternalServerError->value);
        self::assertSame(502, HttpStatusEnum::BadGateway->value);
        self::assertSame(503, HttpStatusEnum::ServiceUnavailable->value);
    }
}
