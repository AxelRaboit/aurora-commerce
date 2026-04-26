<?php

declare(strict_types=1);

namespace App\Contract\Post;

use App\DTO\Post\PostTypeFieldInput;
use App\DTO\Post\PostTypeInput;
use App\Entity\PostType;
use App\Entity\PostTypeField;

interface PostTypeManagerInterface
{
    public function create(PostTypeInput $input): PostType;

    public function update(PostType $postType, PostTypeInput $input): void;

    public function delete(PostType $postType): void;

    public function createField(PostType $postType, PostTypeFieldInput $input): PostTypeField;

    public function updateField(PostTypeField $field, PostTypeFieldInput $input): void;

    public function deleteField(PostTypeField $field): void;

    /**
     * @param list<int> $orderedFieldIds
     */
    public function reorderFields(PostType $postType, array $orderedFieldIds): void;
}
