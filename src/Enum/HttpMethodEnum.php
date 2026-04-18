<?php

declare(strict_types=1);

namespace App\Enum;

enum HttpMethodEnum: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Delete = 'DELETE';
    case Patch = 'PATCH';
    case Head = 'HEAD';
    case Options = 'OPTIONS';
}
