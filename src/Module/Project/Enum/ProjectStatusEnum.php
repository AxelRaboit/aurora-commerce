<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Enum;

enum ProjectStatusEnum: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabelKey(): string
    {
        return 'backend.projects.status_'.$this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
