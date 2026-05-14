<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\ListingCategory\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Module\Ecommerce\ListingCategory\Dto\ListingCategoryInputInterface;
use Aurora\Module\Ecommerce\ListingCategory\Dto\ListingCategoryTranslationInput;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategory;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryInterface;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslation;
use Aurora\Module\Ecommerce\ListingCategory\Entity\ListingCategoryTranslationInterface;
use Aurora\Module\Ecommerce\ListingCategory\Repository\ListingCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ListingCategoryManagerInterface::class)]
class ListingCategoryManager implements ListingCategoryManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ListingCategoryRepository $categoryRepository,
        protected readonly MediaRepository $mediaRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly TranslatorInterface $translator,
        protected readonly SluggerInterface $slugger,
    ) {}

    public function create(ListingCategoryInputInterface $input): ListingCategoryInterface
    {
        $category = $this->createCategory();
        $this->applyInput($category, $input);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->auditCreated($category);

        return $category;
    }

    public function update(ListingCategoryInterface $category, ListingCategoryInputInterface $input): void
    {
        $this->applyInput($category, $input);
        $this->entityManager->flush();

        $this->auditUpdated($category);
    }

    public function delete(ListingCategoryInterface $category): void
    {
        $this->auditDeleted($category);

        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function move(ListingCategoryInterface $category, ?ListingCategoryInterface $newParent, int $position): void
    {
        if ($newParent instanceof ListingCategoryInterface) {
            $this->assertNotDescendant($category, $newParent);
        }

        $category->setParent($newParent);
        $category->setPosition($position);

        $this->entityManager->flush();
        $this->auditUpdated($category);
    }

    protected function createCategory(): ListingCategoryInterface
    {
        return new ListingCategory();
    }

    protected function createTranslation(): ListingCategoryTranslationInterface
    {
        return new ListingCategoryTranslation();
    }

    protected function applyInput(ListingCategoryInterface $category, ListingCategoryInputInterface $input): void
    {
        $parent = null;
        if (null !== $input->getParentId()) {
            $parent = $this->categoryRepository->find($input->getParentId());
            if (!$parent instanceof ListingCategoryInterface) {
                throw new InvalidArgumentException($this->translator->trans('backend.ecommerce.listing_categories.errors.parent_not_found'));
            }
            if ($category->getId() === $parent->getId()) {
                throw new InvalidArgumentException($this->translator->trans('backend.ecommerce.listing_categories.errors.self_parent'));
            }
            $this->assertNotDescendant($category, $parent);
        }

        $category->setParent($parent);
        $category->setPosition($input->getPosition());
        $category->setVisible($input->isVisible());
        $category->setImage(
            null !== $input->getImageId() ? $this->mediaRepository->find($input->getImageId()) : null,
        );

        $inputLocales = array_keys($input->getTranslations());

        foreach ($category->getTranslations() as $existingLocale => $existing) {
            if (!in_array((string) $existingLocale, $inputLocales, true)) {
                $category->removeTranslation($existing);
            }
        }

        foreach ($input->getTranslations() as $locale => $translationInput) {
            $this->applyTranslation($category, (string) $locale, $translationInput);
        }
    }

    protected function applyTranslation(ListingCategoryInterface $category, string $locale, ListingCategoryTranslationInput $input): void
    {
        $translation = $category->getTranslation($locale);
        if (!$translation instanceof ListingCategoryTranslationInterface) {
            $translation = $this->createTranslation();
            $translation->setLocale($locale);
            $translation->setCategory($category);
            $category->addTranslation($translation);
        }

        $translation->setName($input->name);

        $slug = $input->slug;
        if (null === $slug || '' === $slug) {
            $slug = $this->slugger->slug($input->name)->lower()->toString();
        }
        $translation->setSlug($slug);

        $translation->setDescription($input->description);
        $translation->setSeoTitle($input->seoTitle);
        $translation->setSeoDescription($input->seoDescription);
    }

    protected function auditCreated(ListingCategoryInterface $category): void
    {
        $this->auditLogger->log('ecommerce', 'listing_category.created', 'ListingCategory', $category->getId(), $this->auditPayload($category));
    }

    protected function auditUpdated(ListingCategoryInterface $category): void
    {
        $this->auditLogger->log('ecommerce', 'listing_category.updated', 'ListingCategory', $category->getId(), $this->auditPayload($category));
    }

    protected function auditDeleted(ListingCategoryInterface $category): void
    {
        $this->auditLogger->log('ecommerce', 'listing_category.deleted', 'ListingCategory', $category->getId(), $this->auditPayload($category));
    }

    /** @return array<string, mixed> */
    protected function auditPayload(ListingCategoryInterface $category): array
    {
        $locales = [];
        foreach ($category->getTranslations() as $locale => $translation) {
            $locales[(string) $locale] = $translation->getName();
        }

        return [
            'parentId' => $category->getParent()?->getId(),
            'position' => $category->getPosition(),
            'isVisible' => $category->isVisible(),
            'names' => $locales,
        ];
    }

    private function assertNotDescendant(ListingCategoryInterface $category, ListingCategoryInterface $candidateParent): void
    {
        if (null === $category->getId()) {
            return;
        }

        $current = $candidateParent;
        while ($current instanceof ListingCategoryInterface) {
            if ($current->getId() === $category->getId()) {
                throw new InvalidArgumentException($this->translator->trans('backend.ecommerce.listing_categories.errors.cycle_detected'));
            }
            $current = $current->getParent();
        }
    }
}
