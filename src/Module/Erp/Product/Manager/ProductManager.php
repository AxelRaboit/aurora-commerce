<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Erp\Product\Dto\ProductInputInterface;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Entity\ProductInterface;
use Aurora\Module\Erp\Product\Repository\ProductRepository;
use Aurora\Module\Erp\Setting\ErpSettingEnum;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(ProductManagerInterface::class)]
class ProductManager implements ProductManagerInterface
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly ProductRepository $productRepository,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
        protected readonly AuditLogger $auditLogger,
        protected readonly TranslatorInterface $translator,
        protected readonly MediaRepository $mediaRepository,
    ) {}

    public function create(ProductInputInterface $input): ProductInterface
    {
        $this->assertReferenceIsAvailable($input->getReference());

        $product = $this->createProduct();
        $this->applyInput($product, $input);

        if (null !== $input->getReference()) {
            $product->setReference($input->getReference());
        } else {
            $prefix = $this->settingRepository->getOrDefault(ErpSettingEnum::ProductPrefix);
            $product->setReference($this->sequenceGenerator->next($prefix));
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->auditCreated($product);

        return $product;
    }

    public function update(ProductInterface $product, ProductInputInterface $input): void
    {
        $this->assertReferenceIsAvailable($input->getReference(), $product);

        $this->applyInput($product, $input);
        if (null !== $input->getReference()) {
            $product->setReference($input->getReference());
        }

        $this->entityManager->flush();

        $this->auditUpdated($product);
    }

    public function delete(ProductInterface $product): void
    {
        $this->auditDeleted($product);

        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    protected function createProduct(): ProductInterface
    {
        return new Product();
    }

    protected function applyInput(ProductInterface $product, ProductInputInterface $input): void
    {
        $product->setName($input->getName());
        $product->setDescription($input->getDescription());
        $product->setPriceCents($input->getPriceCents());
        $product->setCurrency($input->getCurrency());
        $product->setStatus($input->getStatus());
        $product->setType($input->getType());
        $product->setImage(null !== $input->getImageId() ? $this->mediaRepository->find($input->getImageId()) : null);
        $product->setStockQuantity($input->getStockQuantity());
    }

    protected function auditCreated(ProductInterface $product): void
    {
        $this->auditLogger->log('erp', 'product.created', 'Product', $product->getId(), $this->auditPayload($product));
    }

    protected function auditUpdated(ProductInterface $product): void
    {
        $this->auditLogger->log('erp', 'product.updated', 'Product', $product->getId(), $this->auditPayload($product));
    }

    protected function auditDeleted(ProductInterface $product): void
    {
        $this->auditLogger->log('erp', 'product.deleted', 'Product', $product->getId(), $this->auditPayload($product));
    }

    protected function auditPayload(ProductInterface $product): array
    {
        return ['name' => $product->getName(), 'reference' => $product->getReference()];
    }

    private function assertReferenceIsAvailable(?string $reference, ?ProductInterface $ignore = null): void
    {
        if (null === $reference) {
            return;
        }

        $existing = $this->productRepository->findOneByReference($reference);
        if (!$existing instanceof ProductInterface) {
            return;
        }

        if ($ignore instanceof ProductInterface && $existing->getId() === $ignore->getId()) {
            return;
        }

        throw new InvalidArgumentException($this->translator->trans('backend.erp.products.errors.reference_taken'));
    }
}
