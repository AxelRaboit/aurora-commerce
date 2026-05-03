<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Invoice\Contract\SupplierManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Supplier;
use Aurora\Module\Billing\Invoice\Repository\SupplierRepository;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(SupplierManagerInterface::class)]
final readonly class SupplierManager implements SupplierManagerInterface
{
    use ScalarCoercionTrait;

    /** @var array<string, callable> */
    private array $fieldSetters;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
        private SupplierRepository $supplierRepository,
    ) {
        $this->fieldSetters = [
            // name is NOT NULL — only assign a non-null value, ignore empty strings.
            'name' => fn (Supplier $supplier, ?string $value): ?Supplier => null !== $value ? $supplier->setName($value) : null,
            'vatNumber' => fn (Supplier $supplier, ?string $value): Supplier => $supplier->setVatNumber($value),
            'registrationNumber' => fn (Supplier $supplier, ?string $value): Supplier => $supplier->setRegistrationNumber($value),
            'iban' => fn (Supplier $supplier, ?string $value): Supplier => $supplier->setIban($value),
            'bic' => fn (Supplier $supplier, ?string $value): Supplier => $supplier->setBic($value),
            'email' => fn (Supplier $supplier, ?string $value): Supplier => $supplier->setEmail($value),
            'phone' => fn (Supplier $supplier, ?string $value): Supplier => $supplier->setPhone($value),
            'address' => fn (Supplier $supplier, ?string $value): Supplier => $supplier->setAddress($value),
            'countryCode' => fn (Supplier $supplier, ?string $value): Supplier => $supplier->setCountryCode(null === $value ? null : mb_strtoupper(mb_substr($value, 0, 2))),
        ];
    }

    public function delete(Supplier $supplier): void
    {
        $id = $supplier->getId();
        $name = $supplier->getName();

        $this->entityManager->remove($supplier);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'supplier.deleted', 'Supplier', $id, [
            'name' => $name,
        ]);
    }

    public function updateField(Supplier $supplier, string $field, mixed $value): void
    {
        $setter = $this->fieldSetters[$field] ?? null;
        if (null === $setter) {
            throw new InvalidArgumentException('admin.billing.suppliers.update.unknownField');
        }

        $setter($supplier, $this->stringOrNull($value));
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'supplier.updated', 'Supplier', $supplier->getId(), [
            'field' => $field,
        ]);
    }

    public function findOrCreateFromDraft(InvoiceDraft $draft): ?Supplier
    {
        if (null !== $draft->supplierVatNumber) {
            $existing = $this->supplierRepository->findOneByVatNumber($draft->supplierVatNumber);
            if ($existing instanceof Supplier) {
                return $existing;
            }
        }

        if (null !== $draft->supplierName) {
            $existing = $this->supplierRepository->findOneByNameLike($draft->supplierName);
            if ($existing instanceof Supplier) {
                return $existing;
            }
        }

        if (null === $draft->supplierName) {
            return null;
        }

        $supplier = new Supplier();
        $supplier->setName($draft->supplierName);
        $supplier->setVatNumber($draft->supplierVatNumber);
        $supplier->setRegistrationNumber($draft->supplierRegistrationNumber);
        $supplier->setIban($draft->supplierIban);
        $supplier->setBic($draft->supplierBic);
        $supplier->setEmail($draft->supplierEmail);
        $supplier->setPhone($draft->supplierPhone);
        $supplier->setAddress($draft->supplierAddress);
        $supplier->setCountryCode($draft->supplierCountryCode);

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'supplier.created_from_ocr', 'Supplier', $supplier->getId(), [
            'name' => $supplier->getName(),
            'vatNumber' => $supplier->getVatNumber(),
        ]);

        return $supplier;
    }
}
