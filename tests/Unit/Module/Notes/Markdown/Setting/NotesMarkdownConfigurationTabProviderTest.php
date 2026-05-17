<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Module\Notes\Markdown\Setting;

use Aurora\Module\Configuration\Setting\Configuration\SettingFieldDescriptor;
use Aurora\Module\Notes\Markdown\Setting\MarkdownNoteSettingEnum;
use Aurora\Module\Notes\Markdown\Setting\NotesMarkdownConfigurationTabProvider;
use PHPUnit\Framework\TestCase;

final class NotesMarkdownConfigurationTabProviderTest extends TestCase
{
    public function testProviderExposesOneNotesTabWithEveryEnumCase(): void
    {
        $tabs = (new NotesMarkdownConfigurationTabProvider())->getTabs();

        self::assertCount(1, $tabs);
        $tab = $tabs[0];

        self::assertSame('notes', $tab->id);
        self::assertSame(110, $tab->priority);
        self::assertCount(count(MarkdownNoteSettingEnum::cases()), $tab->fields);

        $keys = array_map(static fn (SettingFieldDescriptor $field): string => $field->key, $tab->fields);
        sort($keys);
        $expected = array_map(static fn (MarkdownNoteSettingEnum $case): string => $case->getKey(), MarkdownNoteSettingEnum::cases());
        sort($expected);
        self::assertSame($expected, $keys);
    }

    public function testFieldDefaultsMatchEnum(): void
    {
        $tabs = (new NotesMarkdownConfigurationTabProvider())->getTabs();
        $byKey = [];
        foreach ($tabs[0]->fields as $field) {
            $byKey[$field->key] = $field;
        }

        $maxEdge = $byKey[MarkdownNoteSettingEnum::ImageMaxEdge->getKey()];
        self::assertSame('int', $maxEdge->type);
        self::assertSame('2048', $maxEdge->defaultValue);

        $quality = $byKey[MarkdownNoteSettingEnum::ImageQualityPct->getKey()];
        self::assertSame('int', $quality->type);
        self::assertSame('85', $quality->defaultValue);
    }
}
