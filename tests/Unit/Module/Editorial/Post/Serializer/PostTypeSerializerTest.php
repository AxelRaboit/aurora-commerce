<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Editorial\Post\Serializer;

use Aurora\Module\Editorial\Post\Entity\PostTypeFieldInterface;
use Aurora\Module\Editorial\Post\Entity\PostTypeInterface;
use Aurora\Module\Editorial\Post\Serializer\PostTypeSerializer;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class PostTypeSerializerTest extends TestCase
{
    private function makeField(int $id, string $name): PostTypeFieldInterface
    {
        $field = $this->createStub(PostTypeFieldInterface::class);
        $field->method('getId')->willReturn($id);
        $field->method('getName')->willReturn($name);
        $field->method('getLabel')->willReturn(ucfirst($name));
        $field->method('getType')->willReturn('text');
        $field->method('isRequired')->willReturn(false);
        $field->method('isTranslatable')->willReturn(true);
        $field->method('getOptions')->willReturn([]);
        $field->method('getPosition')->willReturn(0);

        return $field;
    }

    public function testSerializeWithNoFields(): void
    {
        $postType = $this->createStub(PostTypeInterface::class);
        $postType->method('getId')->willReturn(1);
        $postType->method('getLabel')->willReturn('Article');
        $postType->method('getSlug')->willReturn('article');
        $postType->method('getIcon')->willReturn('file-text');
        $postType->method('hasArchive')->willReturn(true);
        $postType->method('isBuiltIn')->willReturn(false);
        $postType->method('getSupports')->willReturn(['comments']);
        $postType->method('getTaxonomies')->willReturn(new ArrayCollection());
        $postType->method('getFields')->willReturn(new ArrayCollection());

        $result = (new PostTypeSerializer())->serialize($postType);

        self::assertSame(1, $result['id']);
        self::assertSame('Article', $result['label']);
        self::assertSame('article', $result['slug']);
        self::assertSame('file-text', $result['icon']);
        self::assertTrue($result['hasArchive']);
        self::assertFalse($result['isBuiltIn']);
        self::assertSame(['comments'], $result['supports']);
        self::assertSame([], $result['taxonomyIds']);
        self::assertSame([], $result['fields']);
    }

    public function testSerializeIncludesFields(): void
    {
        $postType = $this->createStub(PostTypeInterface::class);
        $postType->method('getId')->willReturn(1);
        $postType->method('getLabel')->willReturn('Post');
        $postType->method('getSlug')->willReturn('post');
        $postType->method('getIcon')->willReturn(null);
        $postType->method('hasArchive')->willReturn(false);
        $postType->method('isBuiltIn')->willReturn(true);
        $postType->method('getSupports')->willReturn([]);
        $postType->method('getTaxonomies')->willReturn(new ArrayCollection());
        $postType->method('getFields')->willReturn(new ArrayCollection([
            $this->makeField(1, 'subtitle'),
            $this->makeField(2, 'tagline'),
        ]));

        $result = (new PostTypeSerializer())->serialize($postType);

        self::assertCount(2, $result['fields']);
        self::assertSame('subtitle', $result['fields'][0]['name']);
        self::assertSame('Subtitle', $result['fields'][0]['label']);
    }
}
