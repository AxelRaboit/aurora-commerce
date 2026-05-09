<?php

declare(strict_types=1);

namespace Aurora\Core\User\Manager;

use Aurora\Core\User\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface UserProfilePhotoManagerInterface
{
    public function upload(User $user, UploadedFile $file): void;

    public function delete(User $user): void;
}
