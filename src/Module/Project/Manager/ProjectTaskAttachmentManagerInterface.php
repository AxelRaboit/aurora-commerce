<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Core\Media\Entity\MediaInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;

interface ProjectTaskAttachmentManagerInterface
{
    /**
     * Attach the given media to the task (skipping already-attached ones).
     *
     * @param list<int> $mediaIds
     *
     * @return int number of attachments effectively added
     */
    public function attach(ProjectTaskInterface $task, array $mediaIds): int;

    public function detach(ProjectTaskInterface $task, MediaInterface $media): void;
}
