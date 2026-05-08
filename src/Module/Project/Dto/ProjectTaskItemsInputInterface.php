<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Dto;

interface ProjectTaskItemsInputInterface
{
    /** @return list<array{label: string, done: bool}> */
    public function getItems(): array;
}
