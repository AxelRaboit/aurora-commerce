<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Entity;

use Aurora\Core\Setting\Entity\Setting;
use PHPUnit\Framework\TestCase;

final class SettingTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $setting = new Setting();

        self::assertSame('', $setting->getKey());
        self::assertNull($setting->getValue());
        self::assertNull($setting->getDescription());
        self::assertSame('string', $setting->getType());
        self::assertNull($setting->getGroup());
    }

    public function testConstructorAssignsValues(): void
    {
        $setting = new Setting(
            key: 'app.name',
            value: 'Aurora',
            description: 'Application name',
            type: 'string',
            group: 'general',
        );

        self::assertSame('app.name', $setting->getKey());
        self::assertSame('Aurora', $setting->getValue());
        self::assertSame('Application name', $setting->getDescription());
        self::assertSame('string', $setting->getType());
        self::assertSame('general', $setting->getGroup());
    }

    public function testGetCastedValueAsBool(): void
    {
        $setting = new Setting(key: 'flag', value: 'true', type: 'bool');
        self::assertTrue($setting->getCastedValue());

        $setting2 = new Setting(key: 'flag', value: 'false', type: 'bool');
        self::assertFalse($setting2->getCastedValue());
    }

    public function testGetCastedValueAsInt(): void
    {
        $setting = new Setting(key: 'count', value: '42', type: 'int');

        self::assertSame(42, $setting->getCastedValue());
    }

    public function testGetCastedValueAsJson(): void
    {
        $setting = new Setting(key: 'config', value: '{"a":1,"b":2}', type: 'json');

        self::assertSame(['a' => 1, 'b' => 2], $setting->getCastedValue());
    }

    public function testGetCastedValueAsString(): void
    {
        $setting = new Setting(key: 'name', value: 'hello', type: 'string');

        self::assertSame('hello', $setting->getCastedValue());
    }

    public function testValueAndDescriptionSetters(): void
    {
        $setting = new Setting(key: 'k');

        $setting->setValue('new');
        self::assertSame('new', $setting->getValue());

        $setting->setDescription('desc');
        self::assertSame('desc', $setting->getDescription());

        $setting->setValue(null);
        self::assertNull($setting->getValue());
    }

    public function testTypeAndGroupSetters(): void
    {
        $setting = new Setting(key: 'k');

        $setting->setType('int');
        self::assertSame('int', $setting->getType());

        $setting->setGroup('advanced');
        self::assertSame('advanced', $setting->getGroup());

        $setting->setGroup(null);
        self::assertNull($setting->getGroup());
    }
}
