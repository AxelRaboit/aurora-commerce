<?php

declare(strict_types=1);

namespace App\Manager\Comment;

use App\Contract\Comment\CommentManagerInterface;
use App\Entity\Comment;
use App\Entity\Post;
use App\Enum\ApplicationParameter\ApplicationParameterEnum;
use App\Enum\Comment\CommentStatusEnum;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CommentManagerInterface::class)]
final readonly class CommentManager implements CommentManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingRepository $settingRepository,
    ) {}

    public function submit(Post $post, string $authorName, string $authorEmail, string $content, ?Comment $parent = null): Comment
    {
        $moderationEnabled = $this->settingRepository->getBoolean(ApplicationParameterEnum::CommentModerationEnabled->value, true);

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setAuthorName($authorName);
        $comment->setAuthorEmail($authorEmail);
        $comment->setContent($content);
        $comment->setStatus($moderationEnabled ? CommentStatusEnum::Pending : CommentStatusEnum::Approved);

        if ($parent instanceof Comment) {
            $comment->setParent($parent);
        }

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    public function approve(Comment $comment): void
    {
        $comment->setStatus(CommentStatusEnum::Approved);
        $this->entityManager->flush();
    }

    public function spam(Comment $comment): void
    {
        $comment->setStatus(CommentStatusEnum::Spam);
        $this->entityManager->flush();
    }

    public function delete(Comment $comment): void
    {
        $this->entityManager->remove($comment);
        $this->entityManager->flush();
    }
}
