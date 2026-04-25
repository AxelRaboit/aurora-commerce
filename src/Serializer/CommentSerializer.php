<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Comment;
use App\Repository\CommentReactionRepository;

use const DATE_ATOM;

final readonly class CommentSerializer
{
    public function __construct(
        private CommentReactionRepository $commentReactionRepository,
    ) {}

    public function serialize(Comment $comment): array
    {
        $firstTranslation = $comment->getPost()->getTranslations()->first();
        $postTitle = false !== $firstTranslation ? ($firstTranslation->getTitle() ?? '') : '';

        $reactionCounts = $this->commentReactionRepository->countByComment((int) $comment->getId());
        $reactionCount = array_sum($reactionCounts);

        return [
            'id' => $comment->getId(),
            'postId' => $comment->getPost()->getId(),
            'postTitle' => $postTitle,
            'authorName' => $comment->getAuthorName(),
            'authorEmail' => $comment->getAuthorEmail(),
            'content' => $comment->getContent(),
            'status' => $comment->getStatus()->value,
            'statusLabel' => $comment->getStatus()->label(),
            'createdAt' => $comment->getCreatedAt()->format(DATE_ATOM),
            'parentId' => $comment->getParent()?->getId(),
            'parentAuthorName' => $comment->getParent()?->getAuthorName(),
            'replyCount' => $comment->getReplies()->count(),
            'reactionCount' => $reactionCount,
        ];
    }
}
