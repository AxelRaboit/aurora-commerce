<?php

declare(strict_types=1);

namespace Aurora\Module\Platform\User\Manager;

use Aurora\Module\Platform\User\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface UserProfilePhotoManagerInterface
{
    public function upload(User $user, UploadedFile $file): void;

    public function delete(User $user): void;
}
