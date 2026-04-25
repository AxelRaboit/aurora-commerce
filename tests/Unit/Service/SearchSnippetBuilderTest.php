<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\SearchSnippetBuilder;
use PHPUnit\Framework\TestCase;

final class SearchSnippetBuilderTest extends TestCase
{
    private SearchSnippetBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SearchSnippetBuilder();
    }

    public function testReturnsEmptyStringForNullContent(): void
    {
        self::assertSame('', $this->builder->build(null, 'query'));
    }

    public function testReturnsEmptyStringForEmptyContent(): void
    {
        self::assertSame('', $this->builder->build('', 'query'));
    }

    public function testBuildsSnippetAroundMatchedToken(): void
    {
        $content = str_repeat('a ', 50).'le mot clé'.str_repeat(' b', 50);
        $snippet = $this->builder->build($content, 'mot');

        self::assertStringContainsString('mot', $snippet);
        self::assertStringContainsString('…', $snippet);
    }

    public function testReturnsFallbackWhenNoMatch(): void
    {
        $content = 'Introduction du contenu sans le terme recherché.';
        $snippet = $this->builder->build($content, 'inexistant');

        self::assertStringStartsWith('Introduction', $snippet);
    }

    public function testSnippetDoesNotExceedRadius(): void
    {
        $content = str_repeat('x ', 200).'match'.str_repeat(' y', 200);
        $snippet = $this->builder->build($content, 'match', 30);

        self::assertLessThanOrEqual(30 * 2 + mb_strlen('match') + 4, mb_strlen($snippet));
    }

    public function testIsCaseInsensitive(): void
    {
        $content = 'Le contenu avec le MOT en majuscules.';
        $snippet = $this->builder->build($content, 'mot');

        self::assertStringContainsString('MOT', $snippet);
    }

    public function testShortContentReturnedAsIs(): void
    {
        $content = 'Court contenu.';
        $snippet = $this->builder->build($content, 'inexistant');

        self::assertSame($content, $snippet);
    }
}
