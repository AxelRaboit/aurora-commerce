<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Ecommerce\Listing\Contract\ListingManagerInterface;
use Aurora\Module\Ecommerce\Listing\DTO\ListingInput;
use Aurora\Module\Ecommerce\Listing\Entity\Listing;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Erp\Product\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ListingManagerInterface::class)]
final readonly class ListingManager implements ListingManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ListingRepository $listingRepository,
        private ProductRepository $productRepository,
        private MediaRepository $mediaRepository,
        private AuditLogger $auditLogger,
        private TranslatorInterface $translator,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
    ) {}

    public function create(ListingInput $input): Listing
    {
        $product = $this->resolveProduct($input);
        $this->assertProductHasNoListing($product);
        $this->assertSlugIsAvailable($input->slug);

        $listing = new Listing();
        $listing->setProduct($product);
        $this->applyInput($listing, $input);
        $prefix = $this->settingRepository->get(ApplicationParameterEnum::EcommerceListingPrefix->value, SequencePrefixEnum::Listing->value) ?? SequencePrefixEnum::Listing->value;
        $listing->setReference($this->sequenceGenerator->next($prefix));

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        $this->auditLogger->log('ecommerce', 'listing.created', 'Listing', $listing->getId(), [
            'slug' => $listing->getSlug(),
            'reference' => $listing->getReference(),
            'productReference' => $product->getReference(),
        ]);

        return $listing;
    }

    public function update(Listing $listing, ListingInput $input): void
    {
        $this->assertSlugIsAvailable($input->slug, $listing);
        $this->applyInput($listing, $input);

        $this->entityManager->flush();

        $this->auditLogger->log('ecommerce', 'listing.updated', 'Listing', $listing->getId(), [
            'slug' => $listing->getSlug(),
        ]);
    }

    public function delete(Listing $listing): void
    {
        $id = $listing->getId();
        $slug = $listing->getSlug();

        $this->entityManager->remove($listing);
        $this->entityManager->flush();

        $this->auditLogger->log('ecommerce', 'listing.deleted', 'Listing', $id, [
            'slug' => $slug,
        ]);
    }

    private function applyInput(Listing $listing, ListingInput $input): void
    {
        $listing->setSlug($input->slug);
        $listing->setMarketingTitle($input->marketingTitle);
        $listing->setMarketingDescription($input->marketingDescription);
        $listing->setVisibleOnShop($input->isVisibleOnShop);
        $listing->setSeoTitle($input->seoTitle);
        $listing->setSeoDescription($input->seoDescription);
        $listing->setFeaturedImage(
            null !== $input->featuredImageId ? $this->mediaRepository->find($input->featuredImageId) : null,
        );
    }

    private function resolveProduct(ListingInput $input): object
    {
        $product = null !== $input->productId ? $this->productRepository->find($input->productId) : null;
        if (null === $product) {
            throw new InvalidArgumentException($this->translator->trans('admin.ecommerce.listings.errors.product_not_found'));
        }

        return $product;
    }

    private function assertProductHasNoListing(object $product): void
    {
        $existing = $this->listingRepository->findOneByProduct($product);
        if ($existing instanceof Listing) {
            throw new InvalidArgumentException($this->translator->trans('admin.ecommerce.listings.errors.product_already_listed'));
        }
    }

    private function assertSlugIsAvailable(string $slug, ?Listing $ignore = null): void
    {
        $existing = $this->listingRepository->findOneBySlug($slug);
        if (!$existing instanceof Listing) {
            return;
        }

        if ($ignore instanceof Listing && $existing->getId() === $ignore->getId()) {
            return;
        }

        throw new InvalidArgumentException($this->translator->trans('admin.ecommerce.listings.errors.slug_taken'));
    }
}
