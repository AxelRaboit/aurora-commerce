<?php

declare(strict_types=1);

namespace Aurora\Core\Dashboard\Service;

use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Menu\Repository\MenuRepository;
use Aurora\Core\User\Repository\UserRepository;
use Aurora\Module\Billing\Invoice\Repository\InvoiceRepository;
use Aurora\Module\Billing\Invoice\Repository\SupplierRepository;
use Aurora\Module\Billing\Ocr\Repository\OcrJobRepository;
use Aurora\Module\Crm\Company\Repository\CompanyRepository;
use Aurora\Module\Crm\Contact\Repository\ContactRepository;
use Aurora\Module\Crm\Deal\Enum\DealStageEnum;
use Aurora\Module\Crm\Deal\Repository\DealRepository;
use Aurora\Module\Ecommerce\Listing\Repository\ListingRepository;
use Aurora\Module\Ecommerce\Order\Repository\OrderRepository;
use Aurora\Module\Editorial\Post\Enum\PostStatusEnum;
use Aurora\Module\Editorial\Post\Repository\PostRepository;
use Aurora\Module\Editorial\Post\Repository\PostTypeRepository;
use Aurora\Module\Erp\Product\Repository\ProductRepository;
use Aurora\Module\Photo\Gallery\Repository\GalleryItemRepository;
use Aurora\Module\Photo\Gallery\Repository\GalleryRepository;
use DateTimeImmutable;
use DateTimeInterface;

final readonly class AdminStatsService
{
    public function __construct(
        private PostRepository $postRepository,
        private PostTypeRepository $postTypeRepository,
        private MediaRepository $mediaRepository,
        private MenuRepository $menuRepository,
        private UserRepository $userRepository,
        private ContactRepository $contactRepository,
        private CompanyRepository $companyRepository,
        private DealRepository $dealRepository,
        private ProductRepository $productRepository,
        private InvoiceRepository $invoiceRepository,
        private SupplierRepository $supplierRepository,
        private OcrJobRepository $ocrJobRepository,
        private OrderRepository $orderRepository,
        private ListingRepository $listingRepository,
        private GalleryRepository $galleryRepository,
        private GalleryItemRepository $galleryItemRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'posts' => $this->getPostStats(),
            'media' => $this->getMediaStats(),
            'menus' => [
                'total' => $this->menuRepository->count([]),
            ],
            'users' => [
                'total' => $this->userRepository->count([]),
            ],
            'crm' => $this->getCrmStats(),
            'erp' => $this->getErpStats(),
            'billing' => $this->getBillingStats(),
            'ecommerce' => $this->getEcommerceStats(),
            'photo' => $this->getPhotoStats(),
            'postsByMonth' => $this->getPostsByMonth(),
            'recentPosts' => $this->getRecentPosts(),
        ];
    }

    /** @return array<string, mixed> */
    private function getCrmStats(): array
    {
        $byStage = $this->dealRepository->countByStage();
        $stages = [];
        foreach (DealStageEnum::cases() as $stage) {
            $stages[] = ['stage' => $stage->value, 'count' => $byStage[$stage->value] ?? 0];
        }

        return [
            'contacts' => $this->contactRepository->count([]),
            'companies' => $this->companyRepository->count([]),
            'deals' => array_sum($byStage),
            'dealsByStage' => $stages,
            'pipelineValue' => $this->dealRepository->getTotalValue(),
            'wonValue' => $this->dealRepository->getTotalValue(DealStageEnum::Won),
        ];
    }

    /** @return array<string, mixed> */
    private function getErpStats(): array
    {
        $byStatus = $this->productRepository->countByStatus();

        return [
            'products' => array_sum($byStatus),
            'draft' => $byStatus['draft'] ?? 0,
            'active' => $byStatus['active'] ?? 0,
            'archived' => $byStatus['archived'] ?? 0,
            'inventoryCents' => $this->productRepository->getTotalInventoryCents(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPostStats(): array
    {
        $byType = [];
        foreach ($this->postTypeRepository->findAll() as $type) {
            $byType[] = [
                'slug' => $type->getSlug(),
                'label' => $type->getLabel(),
                'count' => $this->postRepository->count(['postType' => $type]),
            ];
        }

        return [
            'total' => $this->postRepository->count([]),
            'published' => $this->postRepository->count(['status' => PostStatusEnum::Published, 'deletedAt' => null]),
            'draft' => $this->postRepository->count(['status' => PostStatusEnum::Draft, 'deletedAt' => null]),
            'pendingReview' => $this->postRepository->count(['status' => PostStatusEnum::PendingReview, 'deletedAt' => null]),
            'scheduled' => $this->postRepository->count(['status' => PostStatusEnum::Scheduled, 'deletedAt' => null]),
            'archived' => $this->postRepository->count(['status' => PostStatusEnum::Archived, 'deletedAt' => null]),
            'trashed' => $this->postRepository->countTrashed(),
            'byType' => $byType,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getMediaStats(): array
    {
        return [
            'total' => $this->mediaRepository->count([]),
            'totalSize' => $this->mediaRepository->getTotalStorageSize(),
        ];
    }

    /** @return array<string, mixed> */
    private function getBillingStats(): array
    {
        $byStatus = $this->invoiceRepository->countByStatus();

        return [
            'invoices' => array_sum($byStatus),
            'byStatus' => $byStatus,
            'suppliers' => $this->supplierRepository->count([]),
            'ocrJobs' => $this->ocrJobRepository->count([]),
        ];
    }

    /** @return array<string, mixed> */
    private function getEcommerceStats(): array
    {
        $byStatus = $this->orderRepository->countByStatus();

        return [
            'orders' => array_sum($byStatus),
            'byStatus' => $byStatus,
            'listings' => $this->listingRepository->count([]),
        ];
    }

    /** @return array<string, mixed> */
    private function getPhotoStats(): array
    {
        return [
            'galleries' => $this->galleryRepository->count([]),
            'photos' => $this->galleryItemRepository->count([]),
        ];
    }

    /**
     * @return array<int, array{month: string, count: int}>
     */
    private function getPostsByMonth(): array
    {
        $since = new DateTimeImmutable('-5 months')->modify('first day of this month')->setTime(0, 0);
        $monthCountMap = $this->postRepository->countByMonthSince($since);

        $result = [];
        for ($monthOffset = 5; $monthOffset >= 0; --$monthOffset) {
            $monthKey = new DateTimeImmutable(sprintf('-%d months', $monthOffset))->format('Y-m');
            $result[] = ['month' => $monthKey, 'count' => $monthCountMap[$monthKey] ?? 0];
        }

        return $result;
    }

    /**
     * @return array<int, array{id: int, title: string, status: string, updatedAt: string, postType: string}>
     */
    private function getRecentPosts(): array
    {
        $result = [];
        foreach ($this->postRepository->findRecent(5) as $post) {
            $firstTranslation = $post->getTranslations()->first() ?: null;
            $result[] = [
                'id' => $post->getId(),
                'title' => $firstTranslation ? $firstTranslation->getTitle() : '(sans titre)',
                'status' => $post->getStatus()->value,
                'updatedAt' => $post->getUpdatedAt()->format(DateTimeInterface::ATOM),
                'postType' => $post->getPostType()->getLabel(),
            ];
        }

        return $result;
    }
}
