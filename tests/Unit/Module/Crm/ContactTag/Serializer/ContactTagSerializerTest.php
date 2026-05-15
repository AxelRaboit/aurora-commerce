<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Crm\ContactTag\Serializer;

use Aurora\Module\Crm\ContactTag\Entity\ContactTagInterface;
use Aurora\Module\Crm\ContactTag\Serializer\ContactTagSerializer;
use PHPUnit\Framework\TestCase;

final class ContactTagSerializerTest extends TestCase
{
    private function makeTag(
        int $id = 1,
        string $label = 'VIP',
        string $slug = 'vip',
        string $color = '#ff0000',
    ): ContactTagInterface {
        $tag = $this->createStub(ContactTagInterface::class);
        $tag->method('getId')->willReturn($id);
        $tag->method('getLabel')->willReturn($label);
        $tag->method('getSlug')->willReturn($slug);
        $tag->method('getColor')->willReturn($color);

        return $tag;
    }

    public function testSerializeReturnsAllExpectedFields(): void
    {
        $result = (new ContactTagSerializer())->serialize($this->makeTag());

        self::assertSame(1, $result['id']);
        self::assertSame('VIP', $result['label']);
        self::assertSame('vip', $result['slug']);
        self::assertSame('#ff0000', $result['color']);
    }

    public function testSerializeContainsExactlyExpectedKeys(): void
    {
        $result = (new ContactTagSerializer())->serialize($this->makeTag());

        self::assertSame(['id', 'label', 'slug', 'color'], array_keys($result));
    }

    public function testSerializeWithDifferentColor(): void
    {
        $result = (new ContactTagSerializer())->serialize($this->makeTag(color: '#00ff00'));

        self::assertSame('#00ff00', $result['color']);
    }
}
