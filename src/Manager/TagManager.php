<?php

declare(strict_types=1);

namespace App\Manager;

use App\Contract\TagManagerInterface;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class TagManager implements TagManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
    ) {}

    public function create(string $name): Tag
    {
        $tag = new Tag();
        $tag->setName($name);
        $tag->setSlug($this->slugger->slug($name)->lower()->toString());

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
    }

    public function update(Tag $tag, string $name): void
    {
        $tag->setName($name);
        $tag->setSlug($this->slugger->slug($name)->lower()->toString());

        $this->entityManager->flush();
    }

    public function delete(Tag $tag): void
    {
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }
}
