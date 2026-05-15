<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Auth\View;

use Aurora\Core\Auth\View\RegisterViewBuilder;
use PHPUnit\Framework\TestCase;

final class RegisterViewBuilderTest extends TestCase
{
    public function testRegisterViewReturnsAllFlags(): void
    {
        $view = (new RegisterViewBuilder())->registerView(true, ['email' => 'taken'], ['email' => 'x@x.com']);

        self::assertTrue($view['registrationEnabled']);
        self::assertSame(['email' => 'taken'], $view['errors']);
        self::assertSame(['email' => 'x@x.com'], $view['values']);
    }

    public function testRegisterViewDefaultsToEmpty(): void
    {
        $view = (new RegisterViewBuilder())->registerView(false);

        self::assertFalse($view['registrationEnabled']);
        self::assertSame([], $view['errors']);
        self::assertSame([], $view['values']);
    }

    public function testConfirmViewReturnsPendingEmailAndResent(): void
    {
        $view = (new RegisterViewBuilder())->confirmView('user@example.com', true);

        self::assertSame('user@example.com', $view['pendingEmail']);
        self::assertTrue($view['resent']);
    }

    public function testVerifyViewReturnsSuccess(): void
    {
        self::assertSame(['success' => true], (new RegisterViewBuilder())->verifyView(true));
        self::assertSame(['success' => false], (new RegisterViewBuilder())->verifyView(false));
    }
}
