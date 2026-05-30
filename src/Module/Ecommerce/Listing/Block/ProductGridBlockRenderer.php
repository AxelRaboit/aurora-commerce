<?php

declare(strict_types=1);

namespace Aurora\Module\Ecommerce\Listing\Block;

use Aurora\Core\Content\BlockHtmlSanitizer;
use Aurora\Core\Content\BlockRendererInterface;
use Aurora\Core\Support\Num;
use Aurora\Module\Ecommerce\EcommerceContext;
use Aurora\Module\Ecommerce\Listing\Entity\ListingInterface;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ged\Document\Entity\DocumentInterface;
use Aurora\Module\Ged\Document\Service\DocumentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Renders the `productGrid` Editor.js block — a grid of Ecommerce listings
 * embedded in a post. Lives in the Ecommerce module so the Editorial block
 * renderer never imports Ecommerce; registered via aurora.content_block_renderer.
 */
final readonly class ProductGridBlockRenderer implements BlockRendererInterface
{
    public function __construct(
        private ListingRepository $listingRepository,
        private EcommerceContext $ecommerceContext,
        private DocumentUrlGenerator $documentUrlGenerator,
        private UrlGeneratorInterface $urlGenerator,
        private BlockHtmlSanitizer $sanitizer,
    ) {}

    public function getType(): string
    {
        return 'productGrid';
    }

    /**
     * Block data shape:
     *   { listingIds: int[], columns: 1..4, title?: string }
     */
    public function render(array $data, string $locale): string
    {
        if (!$this->ecommerceContext->isFrontEnabled()) {
            return '';
        }

        $listingIds = array_values(array_filter(
            array_map(intval(...), (array) ($data['listingIds'] ?? [])),
            static fn (int $id): bool => $id > 0,
        ));
        $columns = Num::clamp((int) ($data['columns'] ?? 3), 1, 4);
        $title = $this->sanitizer->safe($data['title'] ?? '');

        if ([] === $listingIds) {
            return '' !== $title ? sprintf('<section class="product-grid my-8"><h2 class="text-2xl font-bold mb-4">%s</h2></section>', $title) : '';
        }

        $found = $this->listingRepository->findBy(['id' => $listingIds]);
        $byId = [];
        foreach ($found as $listing) {
            if ($listing->isVisibleOnShop()) {
                $byId[$listing->getId()] = $listing;
            }
        }

        $items = [];
        foreach ($listingIds as $id) {
            if (isset($byId[$id])) {
                $items[] = $byId[$id];
            }
        }

        if ([] === $items) {
            return '' !== $title ? sprintf('<section class="product-grid my-8"><h2 class="text-2xl font-bold mb-4">%s</h2></section>', $title) : '';
        }

        $gridClass = match ($columns) {
            1 => 'grid-cols-1',
            2 => 'grid-cols-1 sm:grid-cols-2',
            3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
        };

        $cards = '';
        foreach ($items as $listing) {
            $cards .= $this->renderListingCard($listing, $locale);
        }

        return sprintf(
            '<section class="product-grid my-8">%s<div class="grid %s gap-4">%s</div></section>',
            '' !== $title ? sprintf('<h2 class="text-2xl font-bold mb-4">%s</h2>', $title) : '',
            $gridClass,
            $cards,
        );
    }

    private function renderListingCard(ListingInterface $listing, string $locale): string
    {
        $url = $this->urlGenerator->generate('frontend_shop_product', ['locale' => $locale, 'slug' => $listing->getSlug()]);
        $title = htmlspecialchars($listing->getDisplayTitle(), ENT_QUOTES, 'UTF-8');
        $product = $listing->getProduct();
        $priceCents = $product->getPriceCents();
        $price = '';
        if (null !== $priceCents) {
            $amount = $priceCents / (10 ** $product->getCurrency()->decimals());
            $price = sprintf('<p class="text-base font-bold text-accent">%s %s</p>', number_format($amount, 2, ',', ' '), htmlspecialchars($product->getCurrency()->symbol(), ENT_QUOTES, 'UTF-8'));
        }

        $imageHtml = '';
        $featured = $listing->getFeaturedImage() ?? $listing->getProduct()->getImage();
        if ($featured instanceof DocumentInterface) {
            $src = htmlspecialchars((string) ($this->documentUrlGenerator->variantUrl($featured, 'medium') ?? $this->documentUrlGenerator->publicUrl($featured)), ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars($featured->getAlt() ?? $listing->getDisplayTitle(), ENT_QUOTES, 'UTF-8');
            $imageHtml = sprintf('<div class="aspect-square bg-surface-2 overflow-hidden"><img src="%s" alt="%s" class="w-full h-full object-cover" loading="lazy"></div>', $src, $alt);
        }

        return sprintf(
            '<article class="product-card bg-surface border border-line/60 rounded-xl overflow-hidden hover:border-accent transition-colors"><a href="%s" class="block">%s<div class="p-4 space-y-2"><h3 class="text-lg font-semibold text-primary">%s</h3>%s</div></a></article>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $imageHtml,
            $title,
            $price,
        );
    }
}
