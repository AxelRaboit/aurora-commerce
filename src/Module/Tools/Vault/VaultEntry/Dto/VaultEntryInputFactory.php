<?php

declare(strict_types=1);

namespace Aurora\Module\Tools\Vault\VaultEntry\Dto;

use Aurora\Core\Support\Str;
use Aurora\Module\Tools\Vault\Enum\VaultRecordTypeEnum;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VaultEntryInputFactoryInterface::class)]
class VaultEntryInputFactory implements VaultEntryInputFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): VaultEntryInputInterface
    {
        $typeValue = Str::trimFromArray($data, 'type');
        $type = VaultRecordTypeEnum::tryFrom($typeValue) ?? VaultRecordTypeEnum::Login;

        return new VaultEntryInput(
            type: $type,
            title: Str::trimFromArray($data, 'title'),
            url: Str::trimOrNullFromArray($data, 'url'),
            encryptedData: Str::trimFromArray($data, 'encryptedData'),
            iv: Str::trimFromArray($data, 'iv'),
            folderId: isset($data['folderId']) ? (int) $data['folderId'] : null,
            isFavorite: (bool) ($data['isFavorite'] ?? false),
        );
    }
}
