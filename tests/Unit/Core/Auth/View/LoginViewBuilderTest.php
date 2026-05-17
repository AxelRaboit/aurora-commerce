<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Core\Auth\View;

use Aurora\Core\Auth\View\LoginViewBuilder;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class LoginViewBuilderTest extends TestCase
{
    public function testLoginViewIncludesUsernameAndError(): void
    {
        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getBoolean')->willReturn(true);

        $error = new BadCredentialsException('Invalid');
        $view = (new LoginViewBuilder($settings))->loginView('jane@example.com', $error);

        self::assertSame('jane@example.com', $view['last_username']);
        self::assertSame($error, $view['error']);
        self::assertTrue($view['registrationEnabled']);
        self::assertTrue($view['accessRequestEnabled']);
    }

    public function testLoginViewWithNullValues(): void
    {
        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getBoolean')->willReturn(false);

        $view = (new LoginViewBuilder($settings))->loginView(null, null);

        self::assertNull($view['last_username']);
        self::assertNull($view['error']);
        self::assertFalse($view['registrationEnabled']);
    }
}
