<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Enum;

enum ProjectTaskPriorityEnum: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function getLabelKey(): string
    {
        return 'backend.projects.task.priority_'.$this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
