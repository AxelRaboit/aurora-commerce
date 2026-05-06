<?php

declare(strict_types=1);

namespace Aurora\Module\Billing\Invoice\Manager;

use Aurora\Core\Audit\Service\AuditLogger;
use Aurora\Core\Sequence\SequenceGenerator;
use Aurora\Core\Sequence\SequencePrefixEnum;
use Aurora\Core\Setting\Enum\ApplicationParameterEnum;
use Aurora\Core\Setting\Repository\SettingRepository;
use Aurora\Core\Validation\Trait\ScalarCoercionTrait;
use Aurora\Module\Billing\Invoice\Contract\TiersManagerInterface;
use Aurora\Module\Billing\Invoice\Entity\Tiers;
use Aurora\Module\Billing\Invoice\Enum\TiersTypeEnum;
use Aurora\Module\Billing\Invoice\Repository\TiersRepository;
use Aurora\Module\Billing\Ocr\DTO\InvoiceDraft;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(TiersManagerInterface::class)]
final readonly class TiersManager implements TiersManagerInterface
{
    use ScalarCoercionTrait;

    /** @var array<string, callable> */
    private array $fieldSetters;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AuditLogger $auditLogger,
        private TiersRepository $tiersRepository,
        private SequenceGenerator $sequenceGenerator,
        private SettingRepository $settingRepository,
    ) {
        $this->fieldSetters = [
            'name' => fn (Tiers $tiers, ?string $value): ?Tiers => null !== $value ? $tiers->setName($value) : null,
            'vatNumber' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setVatNumber($value),
            'registrationNumber' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setRegistrationNumber($value),
            'iban' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setIban($value),
            'bic' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setBic($value),
            'email' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setEmail($value),
            'phone' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setPhone($value),
            'address' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setAddress($value),
            'countryCode' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setCountryCode(
                null === $value ? null : mb_strtoupper(mb_substr($value, 0, 2))
            ),
            'website' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setWebsite($value),
            'legalForm' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setLegalForm($value),
            'bankName' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setBankName($value),
            'notes' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setNotes($value),
            'reference' => fn (Tiers $tiers, ?string $value): Tiers => $tiers->setReference($value),
        ];
    }

    public function delete(Tiers $tiers): void
    {
        $id = $tiers->getId();
        $name = $tiers->getName();

        $this->entityManager->remove($tiers);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'tiers.deleted', 'Tiers', $id, ['name' => $name]);
    }

    public function updateField(Tiers $tiers, string $field, mixed $value): void
    {
        $setter = $this->fieldSetters[$field] ?? null;
        if (null === $setter) {
            throw new InvalidArgumentException('backend.billing.tiers.update.unknownField');
        }

        $setter($tiers, $this->stringOrNull($value));
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'tiers.updated', 'Tiers', $tiers->getId(), ['field' => $field]);
    }

    public function findOrCreateSupplierFromDraft(InvoiceDraft $draft): ?Tiers
    {
        if (null !== $draft->supplierVatNumber) {
            $existing = $this->tiersRepository->findOneByVatNumber($draft->supplierVatNumber);
            if ($existing instanceof Tiers) {
                return $existing;
            }
        }

        if (null !== $draft->supplierName) {
            $existing = $this->tiersRepository->findOneByNameLike($draft->supplierName, TiersTypeEnum::Supplier);
            if ($existing instanceof Tiers) {
                return $existing;
            }
        }

        if (null === $draft->supplierName) {
            return null;
        }

        $tiers = new Tiers();
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

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::BillingTiersPrefix->value, SequencePrefixEnum::Tiers->value) ?? SequencePrefixEnum::Tiers->value;
        $tiers->setReference($this->sequenceGenerator->next($prefix));

        $this->entityManager->persist($tiers);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'tiers.created_from_ocr', 'Tiers', $tiers->getId(), [
            'type' => TiersTypeEnum::Supplier->value,
            'name' => $tiers->getName(),
        ]);

        return $tiers;
    }

    public function findOrCreateClientFromDraft(InvoiceDraft $draft): ?Tiers
    {
        if (null === $draft->buyerName) {
            return null;
        }

        if (null !== $draft->buyerVatNumber) {
            $existing = $this->tiersRepository->findOneByVatNumber($draft->buyerVatNumber);
            if ($existing instanceof Tiers) {
                return $existing;
            }
        }

        $existing = $this->tiersRepository->findOneByNameLike($draft->buyerName, TiersTypeEnum::Client);
        if ($existing instanceof Tiers) {
            return $existing;
        }

        $tiers = new Tiers();
        $tiers->setType(TiersTypeEnum::Client);
        $tiers->setName($draft->buyerName);
        $tiers->setVatNumber($draft->buyerVatNumber);
        $tiers->setAddress($draft->buyerAddress);
        $tiers->setCountryCode($draft->buyerCountryCode);
        $tiers->setEmail($draft->buyerEmail);
        $tiers->setPhone($draft->buyerPhone);

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::BillingTiersPrefix->value, SequencePrefixEnum::Tiers->value) ?? SequencePrefixEnum::Tiers->value;
        $tiers->setReference($this->sequenceGenerator->next($prefix));

        $this->entityManager->persist($tiers);
        $this->entityManager->flush();

        $this->auditLogger->log('billing', 'tiers.created_from_ocr', 'Tiers', $tiers->getId(), [
            'type' => TiersTypeEnum::Client->value,
            'name' => $tiers->getName(),
        ]);

        return $tiers;
    }

    public function findOrCreate(TiersTypeEnum $type, string $name, ?string $vatNumber): Tiers
    {
        if (null !== $vatNumber) {
            $existing = $this->tiersRepository->findOneByVatNumber($vatNumber);
            if ($existing instanceof Tiers) {
                return $existing;
            }
        }

        $existing = $this->tiersRepository->findOneByNameLike($name, $type);
        if ($existing instanceof Tiers) {
            return $existing;
        }

        $tiers = new Tiers();
        $tiers->setType($type);
        $tiers->setName($name);
        $tiers->setVatNumber($vatNumber);

        $prefix = $this->settingRepository->get(ApplicationParameterEnum::BillingTiersPrefix->value, SequencePrefixEnum::Tiers->value) ?? SequencePrefixEnum::Tiers->value;
        $tiers->setReference($this->sequenceGenerator->next($prefix));

        $this->entityManager->persist($tiers);
        $this->entityManager->flush();

        return $tiers;
    }
}
