<?php

declare(strict_types=1);

namespace Aurora\Core\Twig;

use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Twig\Attribute\AsTwigFunction;

/**
 * Exposes Appearance application parameters to Twig so they can be injected
 * into the page (`window.__auroraConfig`) and consumed by Vue components
 * without an extra AJAX round-trip at mount time.
 *
 * The values are read once per request from the SettingRepository (itself
 * cached in-memory), and decoded on demand so the layout call is cheap.
 */
final class AppearanceExtension
{
    /** @var list<string>|null */
    private ?array $cachedColorPickerPresets = null;

    public function __construct(
        private readonly SettingRepository $settingRepository,
    ) {}

    /**
     * Returns the configured color picker preset palette, falling back to the
     * built-in default if the setting is unset, blank or malformed.
     *
     * @return list<string>
     */
    #[AsTwigFunction(name: 'app_color_presets')]
    public function getColorPickerPresets(): array
    {
        if (null !== $this->cachedColorPickerPresets) {
            return $this->cachedColorPickerPresets;
        }

        $raw = $this->settingRepository->get(
            ApplicationParameterEnum::ColorPickerPresets->value,
            ApplicationParameterEnum::ColorPickerPresets->getDefaultValue(),
        );

        $presets = $this->parsePresets($raw);
        if ([] === $presets) {
            $presets = ApplicationParameterEnum::DEFAULT_COLOR_PICKER_PRESETS;
        }

        return $this->cachedColorPickerPresets = $presets;
    }

    /**
     * @return list<string>
     */
    private function parsePresets(?string $raw): array
    {
        if (null === $raw || '' === $raw) {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $valid = [];
        foreach ($decoded as $hex) {
            if (is_string($hex) && 1 === preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
                $valid[] = $hex;
            }
        }

        return $valid;
    }
}
