<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Service\Dto;

use Aurora\Core\Service\Dto\ServiceInput;
use PHPUnit\Framework\TestCase;

final class ServiceInputTest extends TestCase
{
    public function testGetNameReturnsConstructorValue(): void
    {
        $input = new ServiceInput('Web Development');

        self::assertSame('Web Development', $input->getName());
    }

    public function testNameIsReadOnly(): void
    {
        $input = new ServiceInput('Marketing');

        self::assertSame('Marketing', $input->name);
    }
}
