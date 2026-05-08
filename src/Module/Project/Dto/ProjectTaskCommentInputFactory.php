<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

use Aurora\Core\Support\Str;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ProjectTaskCommentInputFactoryInterface::class)]
class ProjectTaskCommentInputFactory implements ProjectTaskCommentInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): ProjectTaskCommentInputInterface
    {
        return new ProjectTaskCommentInput(content: Str::trimFromArray($data, 'content'));
    }
}
