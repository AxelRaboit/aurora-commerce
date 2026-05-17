<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Block\Setting;

use Aurora\Core\Setting\Configuration\SettingFieldDescriptor;
use Aurora\Module\Notes\Block\Setting\BlockNoteSettingEnum;
use Aurora\Module\Notes\Block\Setting\NotesBlockConfigurationTabProvider;
use PHPUnit\Framework\TestCase;

final class NotesBlockConfigurationTabProviderTest extends TestCase
{
    public function testProviderExposesOneNotesTabWithEveryEnumCase(): void
    {
        $tabs = (new NotesBlockConfigurationTabProvider())->getTabs();

        self::assertCount(1, $tabs);
        $tab = $tabs[0];

        self::assertSame('notes', $tab->id);
        self::assertSame(111, $tab->priority, 'Block comes right after Markdown (110) inside the shared "notes" tab.');
        self::assertCount(count(BlockNoteSettingEnum::cases()), $tab->fields);

        $keys = array_map(static fn (SettingFieldDescriptor $field): string => $field->key, $tab->fields);
        sort($keys);
        $expected = array_map(static fn (BlockNoteSettingEnum $case): string => $case->getKey(), BlockNoteSettingEnum::cases());
        sort($expected);
        self::assertSame($expected, $keys);
    }

    public function testFieldDefaultsMatchEnum(): void
    {
        $tabs = (new NotesBlockConfigurationTabProvider())->getTabs();
        $byKey = [];
        foreach ($tabs[0]->fields as $field) {
            $byKey[$field->key] = $field;
        }

        $maxEdge = $byKey[BlockNoteSettingEnum::ImageMaxEdge->getKey()];
        self::assertSame('int', $maxEdge->type);
        self::assertSame('2048', $maxEdge->defaultValue);

        $quality = $byKey[BlockNoteSettingEnum::ImageQualityPct->getKey()];
        self::assertSame('int', $quality->type);
        self::assertSame('85', $quality->defaultValue);
    }
}
