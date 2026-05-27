<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\Service;

use Aurora\Module\Editorial\Post\Entity\PostInterface;
use Aurora\Module\Editorial\Post\Entity\PostTranslationInterface;
use Aurora\Module\Editorial\Post\Service\PostTextExtractor;
use Aurora\Module\Editorial\PostType\Entity\PostTypeFieldInterface;
use Aurora\Module\Editorial\PostType\Entity\PostTypeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class PostTextExtractorTest extends TestCase
{
    private PostTextExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new PostTextExtractor();
    }

    /**
     * @param list<array<string, mixed>>   $blocks
     * @param array<string, mixed>         $customFields
     * @param list<PostTypeFieldInterface> $typeFields
     */
    private function makeTranslation(
        ?string $metaTitle = null,
        ?string $metaDescription = null,
        ?string $focusKeyword = null,
        array $blocks = [],
        array $customFields = [],
        array $typeFields = [],
    ): PostTranslationInterface {
        $translation = $this->createMock(PostTranslationInterface::class);
        $translation->method('getMetaTitle')->willReturn($metaTitle);
        $translation->method('getMetaDescription')->willReturn($metaDescription);
        $translation->method('getFocusKeyword')->willReturn($focusKeyword);
        $translation->method('getBlocks')->willReturn($blocks);
        $translation->method('getCustomFields')->willReturn($customFields);

        $postType = $this->createMock(PostTypeInterface::class);
        $postType->method('getFields')->willReturn(new ArrayCollection($typeFields));

        $post = $this->createMock(PostInterface::class);
        $post->method('getPostType')->willReturn($postType);

        $translation->method('getPost')->willReturn($post);

        return $translation;
    }

    private function makeField(string $name, string $type, array $options = []): PostTypeFieldInterface
    {
        $field = $this->createMock(PostTypeFieldInterface::class);
        $field->method('getName')->willReturn($name);
        $field->method('getType')->willReturn($type);
        $field->method('getOptions')->willReturn($options);

        return $field;
    }

    public function testExtractConcatenatesAllScalarMetaFields(): void
    {
        $translation = $this->makeTranslation(
            metaTitle: 'Page title',
            metaDescription: 'Description here',
            focusKeyword: 'aurora',
        );

        $result = $this->extractor->extract($translation);

        self::assertStringContainsString('Page title', $result);
        self::assertStringContainsString('Description here', $result);
        self::assertStringContainsString('aurora', $result);
    }

    public function testExtractCollapsesAdjacentWhitespaceAndTrims(): void
    {
        $translation = $this->makeTranslation(
            metaTitle: '  Hello   world  ',
            metaDescription: "\n\nFoo\tBar",
        );

        // All consecutive whitespace gets normalized to a single space and
        // the result is trimmed on both ends — keeps the search index clean.
        self::assertSame('Hello world Foo Bar', $this->extractor->extract($translation));
    }

    public function testExtractSkipsNullAndEmptyParts(): void
    {
        $translation = $this->makeTranslation(metaTitle: null, metaDescription: '   ');

        self::assertSame('', $this->extractor->extract($translation));
    }

    public function testTextFromBlocksRecursesIntoNestedArrays(): void
    {
        $blocks = [
            [
                'type' => 'paragraph',
                'data' => ['text' => 'First paragraph'],
            ],
            [
                'type' => 'list',
                'data' => [
                    'items' => ['Item one', 'Item two', 'Item three'],
                ],
            ],
        ];

        $result = $this->extractor->textFromBlocks($blocks);

        self::assertStringContainsString('First paragraph', $result);
        self::assertStringContainsString('Item one', $result);
        self::assertStringContainsString('Item two', $result);
        self::assertStringContainsString('Item three', $result);
    }

    public function testTextFromBlocksStripsHtmlAndDecodesEntities(): void
    {
        $blocks = [[
            'data' => ['text' => '<p>Hello &amp; <strong>world</strong></p>'],
        ]];

        $result = $this->extractor->textFromBlocks($blocks);

        // Tags are stripped and `&amp;` is decoded so the indexer searches
        // against the plain user-facing text, not the markup.
        self::assertSame('Hello & world', $result);
    }

    public function testTextFromBlocksDropsNonStringNonArrayLeaves(): void
    {
        $blocks = [[
            'data' => [
                'text' => 'visible',
                'count' => 42, // int leaf — skipped
                'enabled' => true, // bool leaf — skipped
            ],
        ]];

        self::assertSame('visible', $this->extractor->textFromBlocks($blocks));
    }

    public function testCustomFieldWithoutDefinitionFallsBackToScalarString(): void
    {
        $translation = $this->makeTranslation(
            customFields: ['legacy_field' => 'still indexed'],
        );

        // No definition for the field → if the value is a string the
        // extractor still includes it (defensive against schema drift).
        self::assertStringContainsString('still indexed', $this->extractor->extract($translation));
    }

    public function testCustomFieldDefinedAsTextIsIncluded(): void
    {
        $translation = $this->makeTranslation(
            customFields: ['author' => 'Alice'],
            typeFields: [$this->makeField('author', 'text')],
        );

        self::assertStringContainsString('Alice', $this->extractor->extract($translation));
    }

    public function testCustomFieldDefinedAsCheckboxIsExcluded(): void
    {
        // Non-text types like checkbox/number/etc. carry no indexable text.
        $translation = $this->makeTranslation(
            customFields: ['featured' => 'true'],
            typeFields: [$this->makeField('featured', 'checkbox')],
        );

        self::assertSame('', $this->extractor->extract($translation));
    }

    public function testSelectCustomFieldResolvesValueToChoiceLabel(): void
    {
        $field = $this->makeField('category', 'select', [
            'choices' => [
                ['value' => 'news', 'label' => 'News'],
                ['value' => 'guide', 'label' => 'Guide article'],
            ],
        ]);
        $translation = $this->makeTranslation(
            customFields: ['category' => 'guide'],
            typeFields: [$field],
        );

        // Stored value is the slug ("guide"); the indexer must surface
        // the user-facing label so searches by label name still match.
        self::assertStringContainsString('Guide article', $this->extractor->extract($translation));
    }

    public function testSelectFieldWithUnknownValueIsSkipped(): void
    {
        $field = $this->makeField('category', 'select', [
            'choices' => [['value' => 'news', 'label' => 'News']],
        ]);
        $translation = $this->makeTranslation(
            customFields: ['category' => 'mystery'],
            typeFields: [$field],
        );

        // Stale value (choice was removed) → nothing to index for it.
        self::assertSame('', $this->extractor->extract($translation));
    }

    public function testEmailAndUrlFieldTypesAreIndexed(): void
    {
        // The whitelist includes url + email as plain-text indexable types.
        $translation = $this->makeTranslation(
            customFields: [
                'contact' => 'jane@example.com',
                'link' => 'https://aurora.test',
            ],
            typeFields: [
                $this->makeField('contact', 'email'),
                $this->makeField('link', 'url'),
            ],
        );

        $result = $this->extractor->extract($translation);

        self::assertStringContainsString('jane@example.com', $result);
        self::assertStringContainsString('https://aurora.test', $result);
    }
}
