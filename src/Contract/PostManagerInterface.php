<?php

declare(strict_types=1);

namespace App\Contract;

use App\DTO\PostInput;
use App\Entity\Post;
use App\Entity\PostRevision;

interface PostManagerInterface
{
    public function create(PostInput $input): Post;

    public function update(Post $post, PostInput $input): void;

    public function delete(Post $post): void;

    public function restoreRevision(Post $post, PostRevision $revision): void;
}
