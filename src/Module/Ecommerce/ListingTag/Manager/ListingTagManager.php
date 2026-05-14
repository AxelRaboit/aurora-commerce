<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingTag\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Module\Ecommerce\ListingTag\Dto\ListingTagInputInterface;
use Aurora\Module\Ecommerce\ListingTag\Dto\ListingTagTranslationInput;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTag;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagInterface;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslation;
use Aurora\Module\Ecommerce\ListingTag\Entity\ListingTagTranslationInterface;
use Aurora\Module\Ecommerce\ListingTag\Repository\ListingTagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsAlias(ListingTagManagerInterface::class)]
class ListingTagManager implements ListingTagManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ListingTagRepository $tagRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly SluggerInterface $slugger,
    ) {}

    public function create(ListingTagInputInterface $input): ListingTagInterface
    {
        $tag = $this->createTag();
        $this->applyInput($tag, $input);

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $this->auditCreated($tag);

        return $tag;
    }

    public function update(ListingTagInterface $tag, ListingTagInputInterface $input): void
    {
        $this->applyInput($tag, $input);
        $this->entityManager->flush();

        $this->auditUpdated($tag);
    }

    public function delete(ListingTagInterface $tag): void
    {
        $this->auditDeleted($tag);

        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }

    protected function createTag(): ListingTagInterface
    {
        return new ListingTag();
    }

    protected function createTranslation(): ListingTagTranslationInterface
    {
        return new ListingTagTranslation();
    }

    protected function applyInput(ListingTagInterface $tag, ListingTagInputInterface $input): void
    {
        $tag->setColor($input->getColor());
        $tag->setVisible($input->isVisible());

        $inputLocales = array_keys($input->getTranslations());

        foreach ($tag->getTranslations() as $existingLocale => $existing) {
            if (!in_array((string) $existingLocale, $inputLocales, true)) {
                $tag->removeTranslation($existing);
            }
        }

        foreach ($input->getTranslations() as $locale => $translationInput) {
            $this->applyTranslation($tag, (string) $locale, $translationInput);
        }
    }

    protected function applyTranslation(ListingTagInterface $tag, string $locale, ListingTagTranslationInput $input): void
    {
        $translation = $tag->getTranslation($locale);
        if (!$translation instanceof ListingTagTranslationInterface) {
            $translation = $this->createTranslation();
            $translation->setLocale($locale);
            $translation->setTag($tag);
            $tag->addTranslation($translation);
        }

        $translation->setName($input->name);

        $slug = $input->slug;
        if (null === $slug || '' === $slug) {
            $slug = $this->slugger->slug($input->name)->lower()->toString();
        }

        $translation->setSlug($slug);

        $translation->setDescription($input->description);
    }

    protected function auditCreated(ListingTagInterface $tag): void
    {
        $this->auditLogger->log('ecommerce', 'listing_tag.created', 'ListingTag', $tag->getId(), $this->auditPayload($tag));
    }

    protected function auditUpdated(ListingTagInterface $tag): void
    {
        $this->auditLogger->log('ecommerce', 'listing_tag.updated', 'ListingTag', $tag->getId(), $this->auditPayload($tag));
    }

    protected function auditDeleted(ListingTagInterface $tag): void
    {
        $this->auditLogger->log('ecommerce', 'listing_tag.deleted', 'ListingTag', $tag->getId(), $this->auditPayload($tag));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(ListingTagInterface $tag): array
    {
        $locales = [];
        foreach ($tag->getTranslations() as $locale => $translation) {
            $locales[(string) $locale] = $translation->getName();
        }

        return [
            'color' => $tag->getColor(),
            'isVisible' => $tag->isVisible(),
            'names' => $locales,
        ];
    }
}
