<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Media\Library\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Module\Ecommerce\Listing\Dto\ListingInputInterface;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\ListingCategory\Repository\ListingCategoryRepository;
use Aurora\Module\Ecommerce\ListingTag\Repository\ListingTagRepository;
use Aurora\Module\Ecommerce\Setting\EcommerceSettingEnum;
use Aurora\Module\Erp\Product\Entity\ProductInterface;
use Aurora\Module\Erp\Product\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ListingManagerInterface::class)]
class ListingManager implements ListingManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ListingRepository $listingRepository,
        protected readonly ProductRepository $productRepository,
        protected readonly MediaRepository $mediaRepository,
        protected readonly ListingCategoryRepository $listingCategoryRepository,
        protected readonly ListingTagRepository $listingTagRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly TranslatorInterface $translator,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {}

    public function create(ListingInputInterface $input): ListingInterface
    {
        $product = $this->resolveProduct($input);
        $this->assertProductHasNoListing($product);
        $this->assertSlugIsAvailable($input->getSlug());

        $listing = $this->createListing();
        $listing->setProduct($product);
        $this->applyInput($listing, $input);
        $prefix = $this->settingRepository->getOrDefault(EcommerceSettingEnum::ListingPrefix);
        $listing->setReference($this->sequenceGenerator->next($prefix));

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        $this->auditCreated($listing);

        return $listing;
    }

    public function update(ListingInterface $listing, ListingInputInterface $input): void
    {
        $this->assertSlugIsAvailable($input->getSlug(), $listing);
        $this->applyInput($listing, $input);

        $this->entityManager->flush();

        $this->auditUpdated($listing);
    }

    public function delete(ListingInterface $listing): void
    {
        $this->auditDeleted($listing);

        $this->entityManager->remove($listing);
        $this->entityManager->flush();
    }

    protected function createListing(): ListingInterface
    {
        return new Listing();
    }

    protected function applyInput(ListingInterface $listing, ListingInputInterface $input): void
    {
        $listing->setSlug($input->getSlug());
        $listing->setMarketingTitle($input->getMarketingTitle());
        $listing->setMarketingDescription($input->getMarketingDescription());
        $listing->setVisibleOnShop($input->isVisibleOnShop());
        $listing->setSeoTitle($input->getSeoTitle());
        $listing->setSeoDescription($input->getSeoDescription());
        $listing->setFeaturedImage(
            null !== $input->getFeaturedImageId() ? $this->mediaRepository->find($input->getFeaturedImageId()) : null,
        );
        $this->applyCategories($listing, $input);
        $this->applyTags($listing, $input);
    }

    protected function applyCategories(ListingInterface $listing, ListingInputInterface $input): void
    {
        $listing->clearCategories();
        $categoryIds = $input->getCategoryIds();
        if ([] === $categoryIds) {
            return;
        }

        $categories = $this->listingCategoryRepository->findBy(['id' => $categoryIds]);
        foreach ($categories as $category) {
            $listing->addCategory($category);
        }
    }

    protected function applyTags(ListingInterface $listing, ListingInputInterface $input): void
    {
        $listing->clearTags();
        $tagIds = $input->getTagIds();
        if ([] === $tagIds) {
            return;
        }

        $tags = $this->listingTagRepository->findBy(['id' => $tagIds]);
        foreach ($tags as $tag) {
            $listing->addTag($tag);
        }
    }

    protected function auditCreated(ListingInterface $listing): void
    {
        $this->auditLogger->log('ecommerce', 'listing.created', 'Listing', $listing->getId(), [
            ...$this->auditPayload($listing),
            'productReference' => $listing->getProduct()->getReference(),
        ]);
    }

    protected function auditUpdated(ListingInterface $listing): void
    {
        $this->auditLogger->log('ecommerce', 'listing.updated', 'Listing', $listing->getId(), $this->auditPayload($listing));
    }

    protected function auditDeleted(ListingInterface $listing): void
    {
        $this->auditLogger->log('ecommerce', 'listing.deleted', 'Listing', $listing->getId(), $this->auditPayload($listing));
    }

    protected function auditPayload(ListingInterface $listing): array
    {
        $categoryIds = [];
        foreach ($listing->getCategories() as $category) {
            $categoryIds[] = $category->getId();
        }

        $tagIds = [];
        foreach ($listing->getTags() as $tag) {
            $tagIds[] = $tag->getId();
        }

        return [
            'slug' => $listing->getSlug(),
            'reference' => $listing->getReference(),
            'category_ids' => $categoryIds,
            'tag_ids' => $tagIds,
        ];
    }

    private function resolveProduct(ListingInputInterface $input): ProductInterface
    {
        $product = null !== $input->getProductId() ? $this->productRepository->find($input->getProductId()) : null;
        if (!$product instanceof ProductInterface) {
            throw new InvalidArgumentException($this->translator->trans('backend.ecommerce.listings.errors.product_not_found'));
        }

        return $product;
    }

    private function assertProductHasNoListing(ProductInterface $product): void
    {
        $existing = $this->listingRepository->findOneByProduct($product);
        if ($existing instanceof ListingInterface) {
            throw new InvalidArgumentException($this->translator->trans('backend.ecommerce.listings.errors.product_already_listed'));
        }
    }

    private function assertSlugIsAvailable(string $slug, ?ListingInterface $ignore = null): void
    {
        $existing = $this->listingRepository->findOneBySlug($slug);
        if (!$existing instanceof ListingInterface) {
            return;
        }

        if ($ignore instanceof ListingInterface && $existing->getId() === $ignore->getId()) {
            return;
        }

        throw new InvalidArgumentException($this->translator->trans('backend.ecommerce.listings.errors.slug_taken'));
    }
}
