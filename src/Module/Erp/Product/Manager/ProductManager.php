<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Module\Erp\Product\Contract\ProductManagerInterface;
use Aurora\Module\Erp\Product\DTO\ProductInput;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Repository\ProductRepository;
use Aurora\Module\Erp\Product\Service\SkuGenerator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ProductManagerInterface::class)]
final readonly class ProductManager implements ProductManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private SkuGenerator $skuGenerator,
        private AuditLogger $auditLogger,
        private TranslatorInterface $translator,
        private MediaRepository $mediaRepository,
    ) {}

    public function create(ProductInput $input): Product
    {
        $this->assertSkuIsAvailable($input->sku);

        $product = new Product();
        $this->applyInput($product, $input);
        $product->setSku($input->sku ?? $this->reservePlaceholderSku());

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        if (null === $input->sku) {
            $product->setSku($this->skuGenerator->generate((int) $product->getId()));
            $this->entityManager->flush();
        }

        $this->auditLogger->log('erp', 'product.created', 'Product', $product->getId(), [
            'name' => $product->getName(),
            'sku' => $product->getSku(),
        ]);

        return $product;
    }

    public function update(Product $product, ProductInput $input): void
    {
        $this->assertSkuIsAvailable($input->sku, $product);

        $this->applyInput($product, $input);
        if (null !== $input->sku) {
            $product->setSku($input->sku);
        }

        $this->entityManager->flush();

        $this->auditLogger->log('erp', 'product.updated', 'Product', $product->getId(), [
            'name' => $product->getName(),
            'sku' => $product->getSku(),
        ]);
    }

    public function delete(Product $product): void
    {
        $id = $product->getId();
        $name = $product->getName();
        $sku = $product->getSku();

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $this->auditLogger->log('erp', 'product.deleted', 'Product', $id, [
            'name' => $name,
            'sku' => $sku,
        ]);
    }

    private function applyInput(Product $product, ProductInput $input): void
    {
        $product->setName($input->name);
        $product->setDescription($input->description);
        $product->setPriceCents($input->priceCents);
        $product->setCurrency($input->currency);
        $product->setStatus($input->status);
        $product->setType($input->type);
        $product->setImage(null !== $input->imageId ? $this->mediaRepository->find($input->imageId) : null);
        $product->setStockQuantity($input->stockQuantity);
    }

    private function assertSkuIsAvailable(?string $sku, ?Product $ignore = null): void
    {
        if (null === $sku) {
            return;
        }

        $existing = $this->productRepository->findOneBySku($sku);
        if (!$existing instanceof Product) {
            return;
        }

        if ($ignore instanceof Product && $existing->getId() === $ignore->getId()) {
            return;
        }

        throw new InvalidArgumentException($this->translator->trans('admin.erp.products.errors.sku_taken'));
    }

    private function reservePlaceholderSku(): string
    {
        return '__pending_'.bin2hex(random_bytes(8));
    }
}
