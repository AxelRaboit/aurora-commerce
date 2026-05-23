<?php

declare(strict_types=1);

namespace Aurora\Module\PersonalFinance\Transaction\Serializer;

use Aurora\Module\PersonalFinance\Transaction\Entity\PersonalFinanceTransactionInterface;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PersonalFinanceTransactionSerializerInterface::class)]
class PersonalFinanceTransactionSerializer implements PersonalFinanceTransactionSerializerInterface
{
    /** @return array<string, mixed> */
    public function serialize(PersonalFinanceTransactionInterface $transaction): array
    {
        return [
            'id' => $transaction->getId(),
            'userId' => $transaction->getUser()->getId(),
            'walletId' => $transaction->getWallet()->getId(),
            'categoryId' => $transaction->getCategory()?->getId(),
            'categoryName' => $transaction->getCategory()?->getName(),
            'type' => $transaction->getType()->value,
            'amount' => $transaction->getAmount(),
            'description' => $transaction->getDescription(),
            'date' => $transaction->getDate()->format('Y-m-d'),
            'tags' => $transaction->getTags(),
            'transferId' => $transaction->getTransferId(),
            'splitId' => $transaction->getSplitId(),
            'attachmentPath' => $transaction->getAttachmentPath(),
            'attachmentOriginalName' => $transaction->getAttachmentOriginalName(),
            'hasAttachment' => $transaction->hasAttachment(),
            'createdAt' => $transaction->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $transaction->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
