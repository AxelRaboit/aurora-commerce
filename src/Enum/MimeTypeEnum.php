<?php

declare(strict_types=1);

namespace App\Enum;

enum MimeTypeEnum: string
{
    case Jpeg = 'image/jpeg';
    case Png = 'image/png';
    case Gif = 'image/gif';
    case Webp = 'image/webp';
    case Svg = 'image/svg+xml';
}
