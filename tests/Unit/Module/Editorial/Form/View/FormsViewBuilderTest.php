<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Form\View;

use Aurora\Core\Locale\Service\LocaleContextInterface;
use Aurora\Module\Editorial\Form\View\FormsViewBuilder;
use PHPUnit\Framework\TestCase;

final class FormsViewBuilderTest extends TestCase
{
    public function testIndexViewReturnsLocales(): void
    {
        $localeContext = $this->createStub(LocaleContextInterface::class);
        $localeContext->method('getActiveLocales')->willReturn(['fr', 'en']);

        $view = (new FormsViewBuilder($localeContext))->indexView();

        self::assertSame(['locales' => ['fr', 'en']], $view);
    }
}
