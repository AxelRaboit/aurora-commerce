<?php

declare(strict_types=1);

namespace Aurora\Module\Notes\Block\Setting;

use Aurora\Core\Setting\Enum\ApplicationParameterEnumInterface;

/**
 * Module-level settings for block notes — image-upload tunables for the
 * built-in `image` block type. Mirrors MarkdownNoteSettingEnum on purpose
 * so each notes flavour keeps its own keys (clients can extend either
 * module independently).
 */
enum BlockNoteSettingEnum: string implements ApplicationParameterEnumInterface
{
    case ImageMaxEdge = 'notes_block_image_max_edge';

    case ImageQualityPct = 'notes_block_image_quality_pct';

    public function getKey(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ImageMaxEdge => 'backend.parameters.notes_block_image_max_edge.label',
            self::ImageQualityPct => 'backend.parameters.notes_block_image_quality_pct.label',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ImageMaxEdge => 'backend.parameters.notes_block_image_max_edge.description',
            self::ImageQualityPct => 'backend.parameters.notes_block_image_quality_pct.description',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::ImageMaxEdge => '2048',
            self::ImageQualityPct => '85',
        };
    }

    public function getType(): string
    {
        return 'int';
    }

    public function getGroup(): string
    {
        return 'notes';
    }
}
