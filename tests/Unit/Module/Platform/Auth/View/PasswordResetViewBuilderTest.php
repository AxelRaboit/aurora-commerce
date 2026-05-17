<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Platform\Auth\View;

use Aurora\Module\Platform\Auth\View\PasswordResetViewBuilder;
use PHPUnit\Framework\TestCase;

final class PasswordResetViewBuilderTest extends TestCase
{
    public function testForgotViewWithStatus(): void
    {
        $view = (new PasswordResetViewBuilder())->forgotView('sent');

        self::assertSame(['status' => 'sent'], $view);
    }

    public function testForgotViewWithNullStatus(): void
    {
        $view = (new PasswordResetViewBuilder())->forgotView(null);

        self::assertSame(['status' => null], $view);
    }

    public function testResetViewIncludesSelectorTokenAndErrors(): void
    {
        $view = (new PasswordResetViewBuilder())->resetView('sel-123', 'tok-456', ['password' => 'too_short']);

        self::assertSame('sel-123', $view['selector']);
        self::assertSame('tok-456', $view['token']);
        self::assertSame(['password' => 'too_short'], $view['errors']);
    }

    public function testResetViewWithDefaultErrors(): void
    {
        $view = (new PasswordResetViewBuilder())->resetView('sel', 'tok');

        self::assertSame([], $view['errors']);
    }
}
