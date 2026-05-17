<?php

declare(strict_types=1);

namespace Aurora\Module\Project\Serializer;

use Aurora\Module\Media\Library\Service\MediaUrlGenerator;
use Aurora\Module\Platform\User\Entity\User;
use Aurora\Module\Project\Entity\ProjectTaskInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ProjectTaskSerializerInterface::class)]
class ProjectTaskSerializer implements ProjectTaskSerializerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly ProjectTaskCommentSerializerInterface $commentSerializer,
        protected readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    public function serialize(ProjectTaskInterface $task): array
    {
        $items = $task->getItems()->toArray();
        $itemsDoneCount = 0;
        foreach ($items as $item) {
            if ($item->isDone()) {
                ++$itemsDoneCount;
            }
        }

        $loggedMinutes = 0;
        foreach ($task->getTimeEntries() as $entry) {
            $loggedMinutes += $entry->getMinutes();
        }

        return [
            'id' => $task->getId(),
            'reference' => $task->getReference(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'columnId' => $task->getColumn()->getId(),
            'priority' => $task->getPriority()->value,
            'priorityLabel' => $this->translator->trans($task->getPriority()->getLabelKey()),
            'assignee' => $task->getAssignee() instanceof User ? [
                'id' => $task->getAssignee()->getId(),
                'name' => $task->getAssignee()->getName(),
            ] : null,
            'dueDate' => $task->getDueDate()?->format('Y-m-d'),
            'position' => $task->getPosition(),
            'storyPoints' => $task->getStoryPoints(),
            'estimateMinutes' => $task->getEstimateMinutes(),
            'loggedMinutes' => $loggedMinutes,
            'labelIds' => array_map(static fn ($label): int => (int) $label->getId(), $task->getLabels()->toArray()),
            'items' => array_map(static fn ($item): array => [
                'id' => $item->getId(),
                'label' => $item->getLabel(),
                'done' => $item->isDone(),
                'position' => $item->getPosition(),
            ], $items),
            'itemsTotal' => count($items),
            'itemsDone' => $itemsDoneCount,
            'comments' => array_map($this->commentSerializer->serialize(...), $task->getComments()->toArray()),
            'commentCount' => $task->getComments()->count(),
            'attachments' => array_map(fn ($media): array => [
                'id' => $media->getId(),
                'name' => $media->getOriginalName(),
                'url' => $this->mediaUrlGenerator->publicUrl($media),
                'mime' => $media->getMimeType(),
            ], $task->getAttachments()->toArray()),
            'watcherIds' => array_map(static fn ($watcher): int => (int) $watcher->getId(), $task->getWatchers()->toArray()),
            'sprintId' => $task->getSprint()?->getId(),
            'sprintName' => $task->getSprint()?->getName(),
            'createdAt' => $task->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $task->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
