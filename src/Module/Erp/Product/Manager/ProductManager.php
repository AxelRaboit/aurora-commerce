<?php

declare(strict_types=1);

namespace Aurora\Module\Erp\Product\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Media\Repository\MediaRepository;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Module\Erp\Product\Contract\ProductManagerInterface;
use Aurora\Module\Erp\Product\Dto\ProductInput;
use Aurora\Module\Erp\Product\Entity\Product;
use Aurora\Module\Erp\Product\Repository\ProductRepository;
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
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
        private AuditLogger $auditLogger,
        private TranslatorInterface $translator,
        private MediaRepository $mediaRepository,
    ) {}

    public function create(ProductInput $input): Product
    {
        $this->assertReferenceIsAvailable($input->reference);

        $product = new Product();
        $this->applyInput($product, $input);

        if (null !== $input->reference) {
            $product->setReference($input->reference);
        } else {
            $prefix = $this->settingRepository->get(ApplicationParameterEnum::ErpProductPrefix->value, SequencePrefixEnum::Product->value) ?? SequencePrefixEnum::Product->value;
            $product->setReference($this->sequenceGenerator->next($prefix));
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->auditLogger->log('erp', 'product.created', 'Product', $product->getId(), [
            'name' => $product->getName(),
            'reference' => $product->getReference(),
        ]);

        return $product;
    }

    public function update(Product $product, ProductInput $input): void
    {
        $this->assertReferenceIsAvailable($input->reference, $product);

        $this->applyInput($product, $input);
        if (null !== $input->reference) {
            $product->setReference($input->reference);
        }

        $this->entityManager->flush();

        $this->auditLogger->log('erp', 'product.updated', 'Product', $product->getId(), [
            'name' => $product->getName(),
            'reference' => $product->getReference(),
        ]);
    }

    public function delete(Product $product): void
    {
        $id = $product->getId();
        $name = $product->getName();
        $reference = $product->getReference();

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $this->auditLogger->log('erp', 'product.deleted', 'Product', $id, [
            'name' => $name,
            'reference' => $reference,
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

    private function assertReferenceIsAvailable(?string $reference, ?Product $ignore = null): void
    {
        if (null === $reference) {
            return;
        }

        $existing = $this->productRepository->findOneByReference($reference);
        if (!$existing instanceof Product) {
            return;
        }

        if ($ignore instanceof Product && $existing->getId() === $ignore->getId()) {
            return;
        }

        throw new InvalidArgumentException($this->translator->trans('backend.erp.products.errors.reference_taken'));
    }
}
