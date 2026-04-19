<?php

declare(strict_types=1);

namespace App\Contract;

use App\Entity\Tag;

interface TagManagerInterface
{
    public function create(string $name): Tag;

    public function update(Tag $tag, string $name): void;

    public function delete(Tag $tag): void;
}
