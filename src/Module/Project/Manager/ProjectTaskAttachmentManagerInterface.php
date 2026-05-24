<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Manager;

use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;

interface ProjectTaskAttachmentManagerInterface
{
    /**
     * Attach the given GED documents to the task (skipping already-attached ones).
     *
     * @param list<int> $documentIds
     *
     * @return int number of attachments effectively added
     */
    public function attach(ProjectTaskInterface $task, array $documentIds): int;

    public function detach(ProjectTaskInterface $task, DocumentInterface $document): void;
}
