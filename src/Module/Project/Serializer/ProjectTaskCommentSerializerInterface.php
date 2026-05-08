<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Project\Entity\ProjectTaskCommentInterface;

interface ProjectTaskCommentSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(ProjectTaskCommentInterface $comment): array;
}
