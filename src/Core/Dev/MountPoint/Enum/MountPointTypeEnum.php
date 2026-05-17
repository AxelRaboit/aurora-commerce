<?php

declare(strict_types=1);

namespace Aurora\Core\Dev\MountPoint\Enum;

enum MountPointTypeEnum: string
{
    case Database = 'database';
    case Api = 'api';
    case Sftp = 'sftp';

    public function getLabel(): string
    {
        return match ($this) {
            self::Database => 'Database',
            self::Api => 'API',
            self::Sftp => 'SFTP',
        };
    }
}
