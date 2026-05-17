<?php

declare(strict_types=1);

namespace Aurora\Tests\Unit\Setting\Enum;

use Aurora\Core\Configuration\Setting\Enum\ApplicationParameterEnum;
use PHPUnit\Framework\TestCase;

use const JSON_THROW_ON_ERROR;

final class ApplicationParameterEnumTest extends TestCase
{
    public function testColorPickerPresetsCaseHasExpectedShape(): void
    {
        $parameter = ApplicationParameterEnum::ColorPickerPresets;

        self::assertSame('color_picker_presets', $parameter->getKey());
        self::assertSame('appearance', $parameter->getGroup());
        self::assertSame('json', $parameter->getType());
        self::assertTrue($parameter->isAdminAccessible());
    }

    public function testColorPickerPresetsDefaultIsValidJsonOfHexColors(): void
    {
        $default = ApplicationParameterEnum::ColorPickerPresets->getDefaultValue();
        $decoded = json_decode($default, true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);
        self::assertCount(16, $decoded);
        foreach ($decoded as $hex) {
            self::assertIsString($hex);
            self::assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $hex);
        }
        self::assertSame(ApplicationParameterEnum::DEFAULT_COLOR_PICKER_PRESETS, $decoded);
    }

    public function testAppearanceGroupIsAdminAccessible(): void
    {
        // Sanity check: the new appearance group must be reachable from the admin
        // settings UI, otherwise the tab would be silently hidden.
        $appearanceCases = array_filter(
            ApplicationParameterEnum::cases(),
            static fn (ApplicationParameterEnum $case): bool => 'appearance' === $case->getGroup(),
        );

        self::assertNotEmpty($appearanceCases);
        foreach ($appearanceCases as $case) {
            self::assertTrue($case->isAdminAccessible(), sprintf('%s should be admin accessible', $case->name));
        }
    }
}
