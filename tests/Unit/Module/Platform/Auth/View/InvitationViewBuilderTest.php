<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Platform\Auth\View;

use Aurora\Module\Platform\Auth\View\InvitationViewBuilder;
use Aurora\Module\Platform\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class InvitationViewBuilderTest extends TestCase
{
    public function testAcceptViewWithDefaultErrors(): void
    {
        $user = new User();
        $view = (new InvitationViewBuilder())->acceptView($user, 'selector', 'token');

        self::assertSame($user, $view['user']);
        self::assertSame('selector', $view['selector']);
        self::assertSame('token', $view['token']);
        self::assertSame([], $view['errors']);
    }

    public function testAcceptViewWithErrors(): void
    {
        $user = new User();
        $view = (new InvitationViewBuilder())->acceptView(
            $user,
            'sel',
            'tok',
            ['password' => 'too_short'],
        );

        self::assertSame(['password' => 'too_short'], $view['errors']);
    }
}
