<?php

declare(strict_types=1);

namespace App\Contract;

use App\Entity\Media;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface MediaManagerInterface
{
    public function upload(UploadedFile $file): Media;
}
