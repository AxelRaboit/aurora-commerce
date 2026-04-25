<?php

declare(strict_types=1);

namespace App\Contract;

use App\Entity\Comment;
use App\Entity\Post;

interface CommentManagerInterface
{
    public function submit(Post $post, string $authorName, string $authorEmail, string $content, ?Comment $parent = null): Comment;

    public function approve(Comment $comment): void;

    public function spam(Comment $comment): void;

    public function delete(Comment $comment): void;
}
