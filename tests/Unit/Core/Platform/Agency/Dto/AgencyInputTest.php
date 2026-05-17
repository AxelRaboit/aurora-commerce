<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Platform\Agency\Dto;

use Aurora\Core\Platform\Agency\Dto\AgencyInput;
use PHPUnit\Framework\TestCase;

final class AgencyInputTest extends TestCase
{
    public function testGetNameReturnsConstructorValue(): void
    {
        $input = new AgencyInput('Aurora Studio');

        self::assertSame('Aurora Studio', $input->getName());
    }

    public function testNameIsReadOnly(): void
    {
        $input = new AgencyInput('Marketing Agency');

        self::assertSame('Marketing Agency', $input->name);
    }
}
