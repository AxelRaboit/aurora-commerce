<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

interface ProjectTaskCommentInputInterface
{
    public function getContent(): string;
}
