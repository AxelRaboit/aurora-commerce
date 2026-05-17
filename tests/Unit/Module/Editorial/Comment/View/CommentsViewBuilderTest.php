<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Comment\View;

use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Editorial\Comment\Repository\CommentRepository;
use Aurora\Module\Editorial\Comment\View\CommentsViewBuilder;
use PHPUnit\Framework\TestCase;

final class CommentsViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsStatsAndFlag(): void
    {
        $commentRepo = $this->createStub(CommentRepository::class);
        $commentRepo->method('countByStatus')->willReturn(['pending' => 3, 'approved' => 10]);

        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getBoolean')->willReturn(true);

        $view = (new CommentsViewBuilder($commentRepo, $settings))->indexView();

        self::assertSame(['pending' => 3, 'approved' => 10], $view['stats']);
        self::assertTrue($view['moderationEnabled']);
    }

    public function testIndexViewWhenModerationDisabled(): void
    {
        $commentRepo = $this->createStub(CommentRepository::class);
        $commentRepo->method('countByStatus')->willReturn([]);

        $settings = $this->createStub(SettingRepository::class);
        $settings->method('getBoolean')->willReturn(false);

        $view = (new CommentsViewBuilder($commentRepo, $settings))->indexView();

        self::assertFalse($view['moderationEnabled']);
    }
}
