<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Enum;

enum ProjectTaskStatusEnum: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function getLabelKey(): string
    {
        return 'backend.projects.task.status_'.$this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
