<?php

declare(strict_types=1);

namespace Aurora\Core\Storage\Enum;

enum StorageAreaEnum: string
{
    case Media = 'media';
    case Ocr = 'ocr';
    case Photo = 'photo';
    case Users = 'users';
    case Ged = 'ged';
}
