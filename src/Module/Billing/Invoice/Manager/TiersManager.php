<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Core\Dev\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Configuration\Setting\Repository\SettingRepository;
use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Entity\TiersInterface;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Billing\Invoice\Repository\TiersRepository;
use Aurora\Module\Billing\Ocr\Dto\InvoiceDraft;
use Aurora\Module\Billing\Setting\BillingSettingEnum;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(TiersManagerInterface::class)]
class TiersManager implements TiersManagerInterface
{
    use ScalarCoercionTrait;

    /** @var array<string, callable> */
    protected readonly array $fieldSetters;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly AuditLogger $auditLogger,
        protected readonly TiersRepository $tiersRepository,
        protected readonly SequenceGenerator $sequenceGenerator,
        protected readonly SettingRepository $settingRepository,
    ) {
        $this->fieldSetters = [
            'name' => fn (TiersInterface $tiers, ?string $value): ?TiersInterface => null !== $value ? $tiers->setName($value) : null,
            'vatNumber' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setVatNumber($value),
            'registrationNumber' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setRegistrationNumber($value),
            'iban' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setIban($value),
            'bic' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setBic($value),
            'email' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setEmail($value),
            'phone' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setPhone($value),
            'address' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setAddress($value),
            'countryCode' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setCountryCode(
                null === $value ? null : mb_strtoupper(mb_substr($value, 0, 2))
            ),
            'website' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setWebsite($value),
            'legalForm' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setLegalForm($value),
            'bankName' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setBankName($value),
            'notes' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setNotes($value),
            'reference' => fn (TiersInterface $tiers, ?string $value): TiersInterface => $tiers->setReference($value),
        ];
    }

    public function delete(TiersInterface $tiers): void
    {
        $this->auditDeleted($tiers);

        $this->entityManager->remove($tiers);
        $this->entityManager->flush();
    }

    public function updateField(TiersInterface $tiers, string $field, mixed $value): void
    {
        $setter = $this->fieldSetters[$field] ?? null;
        if (null === $setter) {
            throw new InvalidArgumentException('backend.billing.tiers.update.unknownField');
        }

        $setter($tiers, $this->stringOrNull($value));
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'tiers.updated', 'Tiers', $tiers->getId(), [
            ...$this->auditPayload($tiers),
            'field' => $field,
        ]);
    }

    public function findOrCreateSupplierFromDraft(InvoiceDraft $draft): ?TiersInterface
    {
        if (null !== $draft->supplierVatNumber) {
            $existing = $this->tiersRepository->findOneByVatNumber($draft->supplierVatNumber);
            if ($existing instanceof TiersInterface) {
                return $existing;
            }
        }

        if (null !== $draft->supplierName) {
            $existing = $this->tiersRepository->findOneByNameLike($draft->supplierName, TiersTypeEnum::Supplier);
            if ($existing instanceof TiersInterface) {
                return $existing;
            }
        }

        if (null === $draft->supplierName) {
            return null;
        }

        $tiers = $this->createTiers();
        $tiers->setType(TiersTypeEnum::Supplier);
        $tiers->setName($draft->supplierName);
        $tiers->setVatNumber($draft->supplierVatNumber);
        $tiers->setRegistrationNumber($draft->supplierRegistrationNumber);
        $tiers->setIban($draft->supplierIban);
        $tiers->setBic($draft->supplierBic);
        $tiers->setEmail($draft->supplierEmail);
        $tiers->setPhone($draft->supplierPhone);
        $tiers->setAddress($draft->supplierAddress);
        $tiers->setCountryCode($draft->supplierCountryCode);
        $tiers->setWebsite($draft->supplierWebsite);
        $tiers->setLegalForm($draft->supplierLegalForm);
        $tiers->setBankName($draft->supplierBankName);
        $this->assignReference($tiers);

        $this->entityManager->persist($tiers);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'tiers.created_from_ocr', 'Tiers', $tiers->getId(), [
            ...$this->auditPayload($tiers),
            'type' => TiersTypeEnum::Supplier->value,
        ]);

        return $tiers;
    }

    public function findOrCreateClientFromDraft(InvoiceDraft $draft): ?TiersInterface
    {
        if (null === $draft->buyerName) {
            return null;
        }

        if (null !== $draft->buyerVatNumber) {
            $existing = $this->tiersRepository->findOneByVatNumber($draft->buyerVatNumber);
            if ($existing instanceof TiersInterface) {
                return $existing;
            }
        }

        $existing = $this->tiersRepository->findOneByNameLike($draft->buyerName, TiersTypeEnum::Client);
        if ($existing instanceof TiersInterface) {
            return $existing;
        }

        $tiers = $this->createTiers();
        $tiers->setType(TiersTypeEnum::Client);
        $tiers->setName($draft->buyerName);
        $tiers->setVatNumber($draft->buyerVatNumber);
        $tiers->setAddress($draft->buyerAddress);
        $tiers->setCountryCode($draft->buyerCountryCode);
        $tiers->setEmail($draft->buyerEmail);
        $tiers->setPhone($draft->buyerPhone);
        $this->assignReference($tiers);

        $this->entityManager->persist($tiers);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'tiers.created_from_ocr', 'Tiers', $tiers->getId(), [
            ...$this->auditPayload($tiers),
            'type' => TiersTypeEnum::Client->value,
        ]);

        return $tiers;
    }

    public function findOrCreate(TiersTypeEnum $type, string $name, ?string $vatNumber): TiersInterface
    {
        if (null !== $vatNumber) {
            $existing = $this->tiersRepository->findOneByVatNumber($vatNumber);
            if ($existing instanceof TiersInterface) {
                return $existing;
            }
        }

        $existing = $this->tiersRepository->findOneByNameLike($name, $type);
        if ($existing instanceof TiersInterface) {
            return $existing;
        }

        $tiers = $this->createTiers();
        $tiers->setType($type);
        $tiers->setName($name);
        $tiers->setVatNumber($vatNumber);
        $this->assignReference($tiers);

        $this->entityManager->persist($tiers);
        $this->entityManager->flush();

        return $tiers;
    }

    protected function createTiers(): TiersInterface
    {
        return new Tiers();
    }

    protected function auditDeleted(TiersInterface $tiers): void
    {
        $this->auditLogger->log('billing', 'tiers.deleted', 'Tiers', $tiers->getId(), $this->auditPayload($tiers));
    }

    /**
     * Base payload for every Tiers audit entry. Override to add custom fields.
     *
     * Note: Tiers has no standard create/update triplet (lifecycle uses
     * domain events: deleted, updated:field, created_from_ocr). Only
     * `auditDeleted` follows the standard hook signature; others stay inline
     * with splat-merged payloads.
     */
    protected function auditPayload(TiersInterface $tiers): array
    {
        return ['name' => $tiers->getName()];
    }

    private function assignReference(TiersInterface $tiers): void
    {
        $prefix = $this->settingRepository->getOrDefault(BillingSettingEnum::TiersPrefix);
        $tiers->setReference($this->sequenceGenerator->next($prefix));
    }
}
