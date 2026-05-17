<?php

declare(strict_types=1);

namespace Aurora\Module\Vault\VaultEntry\View;

use Aurora\Core\Platform\User\Entity\CoreUserInterface;
use Aurora\Module\Vault\VaultEntry\Repository\VaultEntryRepository;
use Aurora\Module\Vault\VaultEntry\Serializer\VaultEntrySerializerInterface;
use Aurora\Module\Vault\VaultFolder\Repository\VaultFolderRepository;
use Aurora\Module\Vault\VaultFolder\Serializer\VaultFolderSerializerInterface;
use Aurora\Module\Vault\VaultUserConfig\Entity\VaultUserConfigInterface;
use Aurora\Module\Vault\VaultUserConfig\Repository\VaultUserConfigRepository;
use Aurora\Module\Vault\VaultUserConfig\Serializer\VaultUserConfigSerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class VaultEntriesViewBuilder
{
    public function __construct(
        private VaultEntryRepository $vaultEntryRepository,
        private VaultEntrySerializerInterface $vaultEntrySerializer,
        private VaultFolderRepository $vaultFolderRepository,
        private VaultFolderSerializerInterface $vaultFolderSerializer,
        private VaultUserConfigRepository $vaultUserConfigRepository,
        private VaultUserConfigSerializerInterface $vaultUserConfigSerializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @return array<string, mixed> */
    public function indexView(CoreUserInterface $user): array
    {
        $config = $this->vaultUserConfigRepository->findOneByUser($user);
        $entries = [];
        $folders = [];
        $vaultConfig = null;
        if ($config instanceof VaultUserConfigInterface) {
            $entries = $this->vaultEntryRepository->findByUserWithFolder($user);
            $folders = $this->vaultFolderRepository->findAllByUserOrdered($user);
            $vaultConfig = $this->vaultUserConfigSerializer->serialize($config);
        }

        return [
            'vaultConfig' => $vaultConfig,
            'entries' => array_map($this->vaultEntrySerializer->serialize(...), $entries),
            'folders' => array_map($this->vaultFolderSerializer->serialize(...), $folders),
            'setupPath' => $this->urlGenerator->generate('backend_vault_config_setup'),
            'changeMasterPasswordPath' => $this->urlGenerator->generate('backend_vault_config_change_master_password'),
            'destroyVaultPath' => $this->urlGenerator->generate('backend_vault_config_destroy'),
            'createEntryPath' => $this->urlGenerator->generate('backend_vault_entries_create'),
            'updateEntryPath' => $this->urlGenerator->generate('backend_vault_entries_update', ['id' => '__id__']),
            'deleteEntryPath' => $this->urlGenerator->generate('backend_vault_entries_delete', ['id' => '__id__']),
            'toggleFavoritePath' => $this->urlGenerator->generate('backend_vault_entries_toggle_favorite', ['id' => '__id__']),
            'moveEntryPath' => $this->urlGenerator->generate('backend_vault_entries_move', ['id' => '__id__']),
            'createFolderPath' => $this->urlGenerator->generate('backend_vault_folders_create'),
            'updateFolderPath' => $this->urlGenerator->generate('backend_vault_folders_update', ['id' => '__id__']),
            'deleteFolderPath' => $this->urlGenerator->generate('backend_vault_folders_delete', ['id' => '__id__']),
        ];
    }
}
