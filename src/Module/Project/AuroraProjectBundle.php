<?php

declare(strict_types=1);

namespace Aurora\Module\Project;

use Aurora\Core\Bundle\AbstractAuroraModuleBundle;
use Aurora\Module\Project\Entity\Project;
use Aurora\Module\Project\Entity\ProjectColumn;
use Aurora\Module\Project\Entity\ProjectColumnInterface;
use Aurora\Module\Project\Entity\ProjectInterface;
use Aurora\Module\Project\Entity\ProjectLabel;
use Aurora\Module\Project\Entity\ProjectLabelInterface;
use Aurora\Module\Project\Entity\ProjectSavedView;
use Aurora\Module\Project\Entity\ProjectSavedViewInterface;
use Aurora\Module\Project\Entity\ProjectSprint;
use Aurora\Module\Project\Entity\ProjectSprintInterface;
use Aurora\Module\Project\Entity\ProjectTask;
use Aurora\Module\Project\Entity\ProjectTaskComment;
use Aurora\Module\Project\Entity\ProjectTaskCommentInterface;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use Aurora\Module\Project\Entity\ProjectTaskItem;
use Aurora\Module\Project\Entity\ProjectTaskItemInterface;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntry;
use Aurora\Module\Project\Entity\ProjectTaskTimeEntryInterface;

/** Self-contained bundle for the Project module. @see AbstractAuroraModuleBundle */
final class AuroraProjectBundle extends AbstractAuroraModuleBundle
{
    protected function moduleName(): string
    {
        return 'Project';
    }

    protected function resolveTargetEntities(): array
    {
        return [
            ProjectInterface::class => Project::class,
            ProjectColumnInterface::class => ProjectColumn::class,
            ProjectLabelInterface::class => ProjectLabel::class,
            ProjectSavedViewInterface::class => ProjectSavedView::class,
            ProjectSprintInterface::class => ProjectSprint::class,
            ProjectTaskInterface::class => ProjectTask::class,
            ProjectTaskCommentInterface::class => ProjectTaskComment::class,
            ProjectTaskItemInterface::class => ProjectTaskItem::class,
            ProjectTaskTimeEntryInterface::class => ProjectTaskTimeEntry::class,
        ];
    }
}
