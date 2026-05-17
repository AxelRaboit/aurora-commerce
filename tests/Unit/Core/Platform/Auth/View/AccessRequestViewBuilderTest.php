<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Platform\Auth\View;

use Aurora\Core\Platform\Auth\View\AccessRequestViewBuilder;
use PHPUnit\Framework\TestCase;

final class AccessRequestViewBuilderTest extends TestCase
{
    public function testFormViewWithDefaults(): void
    {
        $view = (new AccessRequestViewBuilder())->formView(true);

        self::assertTrue($view['accessRequestEnabled']);
        self::assertSame([], $view['errors']);
        self::assertSame([], $view['values']);
    }

    public function testFormViewWithErrorsAndValues(): void
    {
        $view = (new AccessRequestViewBuilder())->formView(
            accessRequestEnabled: false,
            errors: ['email' => 'invalid'],
            values: ['email' => 'x@x.com'],
        );

        self::assertFalse($view['accessRequestEnabled']);
        self::assertSame(['email' => 'invalid'], $view['errors']);
        self::assertSame(['email' => 'x@x.com'], $view['values']);
    }
}
