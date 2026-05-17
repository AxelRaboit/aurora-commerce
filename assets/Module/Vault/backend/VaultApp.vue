<script setup>
import { ref, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { useVaultEntryActions } from '@vault/backend/composables/useVaultEntryActions.js';
import { useVaultCrypto } from '@vault/backend/composables/useVaultCrypto.js';
import { useVaultForm } from '@vault/backend/composables/useVaultForm.js';
import { useVaultFolderTree } from '@vault/backend/composables/useVaultFolderTree.js';
import { useVaultFolders } from '@vault/backend/composables/useVaultFolders.js';
import { useVaultDragDrop } from '@vault/backend/composables/useVaultDragDrop.js';
import { useVaultNavigation } from '@vault/backend/composables/useVaultNavigation.js';
import { useVaultUnlockLifecycle } from '@vault/backend/composables/useVaultUnlockLifecycle.js';
import { useVaultFilter } from '@vault/backend/composables/useVaultFilter.js';
import { useVaultFolderAccordion } from '@vault/backend/composables/useVaultFolderAccordion.js';
import { useVaultChangeMasterPassword } from '@vault/backend/composables/useVaultChangeMasterPassword.js';
import { useVaultDestroyVault } from '@vault/backend/composables/useVaultDestroyVault.js';
import { ICONS } from '@vault/backend/utils/recordTypes.js';
import VaultSetupScreen from '@vault/backend/components/VaultSetupScreen.vue';
import VaultUnlockScreen from '@vault/backend/components/VaultUnlockScreen.vue';
import VaultEntryFormModal from '@vault/backend/components/VaultEntryFormModal.vue';
import VaultEntryViewModal from '@vault/backend/components/VaultEntryViewModal.vue';
import VaultEntryRow from '@vault/backend/components/VaultEntryRow.vue';
import VaultChangeMasterPasswordModal from '@vault/backend/components/VaultChangeMasterPasswordModal.vue';
import VaultDestroyModal from '@vault/backend/components/VaultDestroyModal.vue';
import AppButton from '@shared/components/action/AppButton.vue';
import AppIconButton from '@shared/components/action/AppIconButton.vue';
import AppListItemButton from '@shared/components/action/AppListItemButton.vue';

import AppTextLinkButton from '@shared/components/action/AppTextLinkButton.vue';
import AppInput from '@shared/components/form/input/AppInput.vue';
import AppToggle from '@shared/components/form/toggle/AppToggle.vue';
import AppMultiselect from '@shared/components/form/select/AppMultiselect.vue';
import AppSearchInput from '@shared/components/form/input/AppSearchInput.vue';
import AppModal from '@shared/components/overlay/AppModal.vue';
import AppModalFooter from '@shared/components/overlay/AppModalFooter.vue';
import AppNoData from '@shared/components/feedback/AppNoData.vue';
import AppColorField from '@shared/components/form/picker/AppColorField.vue';
import {
    Plus, Lock, Star, Pencil, Trash2, X, Save, Home, Layers, Folder, FolderOpen, ShieldCheck,
    ChevronRight, ChevronDown, Eye,
} from 'lucide-vue-next';

const props = defineProps({
    vaultConfig: { type: Object, default: null },
    entries: { type: Array, default: () => [] },
    folders: { type: Array, default: () => [] },
    setupPath: { type: String, required: true },
    createEntryPath: { type: String, required: true },
    updateEntryPath: { type: String, required: true },
    deleteEntryPath: { type: String, required: true },
    toggleFavoritePath: { type: String, required: true },
    moveEntryPath: { type: String, required: true },
    createFolderPath: { type: String, required: true },
    updateFolderPath: { type: String, required: true },
    deleteFolderPath: { type: String, required: true },
    changeMasterPasswordPath: { type: String, required: true },
    destroyVaultPath: { type: String, required: true },
    extraFields: { type: Array, default: () => [] },
});

const { t } = useI18n();
const crypto = useVaultCrypto();

const vaultConfig = ref(props.vaultConfig);
const entries = ref([...props.entries]);
const folders = ref([...props.folders]);
const decryptedCache = reactive({});

const { currentFolderId, showFavorites, allView, allFoldersView, navigate, showAllEntries, showAllFolders, toggleFavorites } = useVaultNavigation();


const { flatFolders, allFlatFolders, breadcrumbs, collapsedFolderIds, toggleCollapse } =
    useVaultFolderTree(folders, currentFolderId);

const {
    folderModal, folderForm, folderErrors, folderSaving,
    openCreateFolder, openEditFolder, submitFolder,
    pendingDelete: pendingFolderDelete, deleteLoading, confirmDeleteFolder, doDeleteFolder, folderParentSelectOptions,
} = useVaultFolders(props, folders, currentFolderId, entries, navigate);

const { isUnlocked, onSetupComplete, onUnlock, lockVault: lockVaultFn } = useVaultUnlockLifecycle(crypto, vaultConfig, entries, decryptedCache);

function lockVault() {
    lockVaultFn(decryptedCache);
}

const nav = { currentFolderId, showFavorites, allView, allFoldersView };
const { searchQuery, filteredEntries, filteredFolders, emptyMessage } = useVaultFilter(entries, folders, nav);

const { onEntrySuccess, pendingDelete, entryDeleteLoading, confirmDelete, doDelete, toggleFavorite } =
    useVaultEntryActions(crypto, entries, decryptedCache, props.deleteEntryPath, props.toggleFavoritePath);

const vaultForm = useVaultForm(crypto, props, onEntrySuccess);

const {
    dragOverFolderId, rootDragOver,
    onEntryDragStart, onFolderDragStart,
    onFolderDragOver, onRootDragOver, onDragLeave, onFolderDrop,
} = useVaultDragDrop(props, entries, folders, currentFolderId);

const viewingEntry = ref(null);
function openView(entry) { viewingEntry.value = entry; }
function closeView() { viewingEntry.value = null; }

const changePwd = useVaultChangeMasterPassword(
    props.changeMasterPasswordPath,
    vaultConfig,
    entries,
    decryptedCache,
    (newSalt, newMasterPassword, config) => {
        vaultConfig.value = config;
        crypto.unlock(newMasterPassword, newSalt);
    },
);

const destroyVault = useVaultDestroyVault(props.destroyVaultPath, vaultConfig, entries, () => {
    vaultConfig.value = null;
    entries.value = [];
    folders.value = [];
    crypto.lock();
});

const { expandedFolderIds, toggleFolderExpanded, rootFolders, folderEntryCounts, folderChildCounts, entriesInFolder } = useVaultFolderAccordion(entries, folders);

</script>

<template>
    <div class="space-y-4">
        <template v-if="!vaultConfig">
            <VaultSetupScreen :setup-path="setupPath" v-on:setup-complete="onSetupComplete" />
        </template>

        <template v-else-if="!isUnlocked">
            <VaultUnlockScreen
                :salt-base64="vaultConfig.argon2Salt"
                :has-entries="entries.length > 0"
                v-on:unlock="onUnlock"
            />
        </template>

        <template v-else>
            <div class="flex items-center gap-2">
                <AppSearchInput v-model="searchQuery" class="flex-1 min-w-0" :placeholder="t('vault.entries.searchPlaceholder')" />
                <AppButton variant="primary" size="md" class="shrink-0" v-on:click="vaultForm.openCreate()">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    <span class="hidden sm:inline">{{ t('vault.entries.add') }}</span>
                </AppButton>
                <div class="flex items-center gap-0.5 shrink-0 border-l border-line pl-2">
                    <AppIconButton :title="t('vault.unlock.lock')" v-on:click="lockVault">
                        <Lock class="w-4 h-4" :stroke-width="1.5" />
                    </AppIconButton>
                    <AppIconButton :title="t('vault.change_password.title')" v-on:click="changePwd.open()">
                        <ShieldCheck class="w-4 h-4" :stroke-width="1.5" />
                    </AppIconButton>
                    <AppIconButton color="rose" :title="t('vault.destroy.button')" v-on:click="destroyVault.open()">
                        <Trash2 class="w-4 h-4" :stroke-width="1.5" />
                    </AppIconButton>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-4">
                <aside class="lg:w-60 shrink-0 space-y-1">
                    <AppListItemButton class="rounded-lg" :active="allFoldersView && !showFavorites" v-on:click="showAllFolders">
                        <template #icon><FolderOpen class="w-4 h-4" :stroke-width="2" /></template>
                        {{ t('vault.folders.all_folders') }}
                    </AppListItemButton>

                    <AppListItemButton class="rounded-lg" :active="allView && !showFavorites" v-on:click="showAllEntries">
                        <template #icon><Layers class="w-4 h-4" :stroke-width="2" /></template>
                        {{ t('vault.folders.all') }}
                    </AppListItemButton>

                    <AppListItemButton class="rounded-lg" :active="showFavorites" v-on:click="toggleFavorites">
                        <template #icon><Star class="w-4 h-4" :stroke-width="2" :fill="showFavorites ? 'currentColor' : 'none'" /></template>
                        {{ t('vault.folders.favorites') }}
                    </AppListItemButton>

                    <div class="border-t border-line/40 my-2" />

                    <div class="flex items-center gap-1 px-1">
                        <span class="text-xs font-semibold text-secondary uppercase tracking-wide flex-1">{{ t('vault.folders.folders') }}</span>
                        <AppIconButton :title="t('vault.folders.add')" v-on:click="openCreateFolder">
                            <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                        </AppIconButton>
                    </div>

                    <AppListItemButton
                        class="rounded-lg"
                        :active="!allView && !allFoldersView && currentFolderId === null && !showFavorites"
                        :class="rootDragOver ? 'ring-2 ring-accent-500' : ''"
                        v-on:click="navigate(null)"
                        v-on:dragover="onRootDragOver"
                        v-on:dragleave="onDragLeave"
                        v-on:drop="onFolderDrop($event, null)"
                    >
                        <template #icon><Home class="w-4 h-4" :stroke-width="2" /></template>
                        {{ t('vault.folders.root') }}
                    </AppListItemButton>

                    <div
                        v-for="folder in flatFolders"
                        :key="folder.id"
                        class="group flex items-center gap-1"
                        :style="{ paddingLeft: `${folder.depth * 1}rem` }"
                        draggable="true"
                        v-on:dragstart="onFolderDragStart($event, folder)"
                    >
                        <AppIconButton
                            v-if="folder.childCount > 0"
                            size="compact"
                            class="-ml-1 shrink-0"
                            :title="collapsedFolderIds.has(folder.id) ? t('shared.common.expand') : t('shared.common.collapse')"
                            v-on:click.stop="toggleCollapse(folder.id)"
                        >
                            <ChevronRight v-if="collapsedFolderIds.has(folder.id)" class="w-3 h-3" :stroke-width="2" />
                            <ChevronDown v-else class="w-3 h-3" :stroke-width="2" />
                        </AppIconButton>
                        <span v-else class="w-4 shrink-0" />

                        <AppListItemButton
                            class="flex-1 min-w-0 rounded-lg"
                            :active="!allView && !allFoldersView && !showFavorites && currentFolderId === folder.id"
                            :class="dragOverFolderId === folder.id ? 'ring-2 ring-accent-500' : ''"
                            v-on:click="navigate(folder.id)"
                            v-on:dragover="onFolderDragOver($event, folder.id)"
                            v-on:dragleave="onDragLeave"
                            v-on:drop="onFolderDrop($event, folder.id)"
                        >
                            <template #icon>
                                <span
                                    v-if="folder.color"
                                    class="w-2.5 h-2.5 rounded-full shrink-0"
                                    :style="{ backgroundColor: folder.color }"
                                />
                                <Folder v-else class="w-4 h-4 shrink-0" :stroke-width="2" />
                            </template>
                            {{ folder.name }}
                        </AppListItemButton>

                        <div class="opacity-0 group-hover:opacity-100 flex gap-0.5 transition-opacity shrink-0">
                            <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEditFolder(folder)">
                                <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDeleteFolder(folder)">
                                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </div>

                    <AppNoData v-if="!folders.length" :message="t('vault.folders.empty')" />
                </aside>

                <main class="flex-1 min-w-0 space-y-3">
                    <nav v-if="!allView && !allFoldersView && !showFavorites" class="flex items-center gap-1 flex-wrap">
                        <AppTextLinkButton color="muted" v-on:click="navigate(null)">
                            <Home class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t('vault.folders.root') }}
                        </AppTextLinkButton>
                        <template v-for="crumb in breadcrumbs" :key="crumb.id">
                            <ChevronRight class="w-3 h-3 shrink-0 text-muted" :stroke-width="2" />
                            <AppTextLinkButton color="muted" v-on:click="navigate(crumb.id)">
                                {{ crumb.name }}
                            </AppTextLinkButton>
                        </template>
                    </nav>

                    <template v-if="searchQuery.trim()">
                        <div v-if="filteredFolders.length" class="space-y-1">
                            <p class="text-xs font-semibold text-secondary uppercase tracking-wide px-1">
                                {{ t('vault.folders.folders') }}
                                <span class="font-normal normal-case">({{ filteredFolders.length }})</span>
                            </p>
                            <div
                                v-for="folder in filteredFolders"
                                :key="folder.id"
                                class="rounded-lg border border-line bg-surface overflow-hidden"
                            >
                                <AppButton
                                    variant="ghost"
                                    size="none"
                                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-2 transition-colors text-left"
                                    v-on:click="toggleFolderExpanded(folder.id)"
                                >
                                    <span
                                        v-if="folder.color"
                                        class="w-3 h-3 rounded-full shrink-0"
                                        :style="{ backgroundColor: folder.color }"
                                    />
                                    <Folder v-else class="w-4 h-4 shrink-0 text-secondary" :stroke-width="1.5" />
                                    <span class="flex-1 text-sm font-medium text-primary truncate">{{ folder.name }}</span>
                                    <span class="text-xs text-muted shrink-0">
                                        {{ folderEntryCounts[folder.id] ?? 0 }} {{ t('vault.entries.count') }}
                                    </span>
                                    <ChevronDown v-if="expandedFolderIds.has(folder.id)" class="w-4 h-4 text-muted shrink-0 ml-1" :stroke-width="2" />
                                    <ChevronRight v-else class="w-4 h-4 text-muted shrink-0 ml-1" :stroke-width="2" />
                                </AppButton>
                                <div v-if="expandedFolderIds.has(folder.id)" class="border-t border-line/60">
                                    <div v-if="entriesInFolder(folder.id).length" class="divide-y divide-line/40">
                                        <VaultEntryRow
                                            v-for="entry in entriesInFolder(folder.id)"
                                            :key="entry.id"
                                            :entry="entry"
                                            :show-folder="false"
                                            class="rounded-none border-0"
                                            v-on:dragstart="onEntryDragStart($event, entry)"
                                            v-on:view="openView(entry)"
                                            v-on:edit="vaultForm.openEdit(entry, decryptedCache[entry.id] ?? {})"
                                            v-on:delete="confirmDelete(entry)"
                                            v-on:toggle-favorite="toggleFavorite(entry)"
                                        />
                                    </div>
                                    <p v-else class="px-4 py-3 text-sm text-muted">{{ t('vault.entries.emptyFolder') }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="filteredEntries.length" class="space-y-1">
                            <p class="text-xs font-semibold text-secondary uppercase tracking-wide px-1">
                                {{ t('vault.entries.all_entries_label') }}
                                <span class="font-normal normal-case">({{ filteredEntries.length }})</span>
                            </p>
                            <VaultEntryRow
                                v-for="entry in filteredEntries"
                                :key="entry.id"
                                :entry="entry"
                                v-on:dragstart="onEntryDragStart($event, entry)"
                                v-on:view="openView(entry)"
                                v-on:edit="vaultForm.openEdit(entry, decryptedCache[entry.id] ?? {})"
                                v-on:delete="confirmDelete(entry)"
                                v-on:toggle-favorite="toggleFavorite(entry)"
                            />
                        </div>

                        <AppNoData
                            v-if="!filteredFolders.length && !filteredEntries.length"
                            :message="t('vault.entries.empty')"
                        />
                    </template>

                    <template v-else-if="allFoldersView && !showFavorites">
                        <div v-if="rootFolders.length" class="space-y-1">
                            <div
                                v-for="folder in rootFolders"
                                :key="folder.id"
                                class="rounded-lg border border-line bg-surface overflow-hidden"
                            >
                                <AppButton
                                    variant="ghost"
                                    size="none"
                                    class="w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-2 transition-colors text-left"
                                    v-on:click="toggleFolderExpanded(folder.id)"
                                >
                                    <span
                                        v-if="folder.color"
                                        class="w-3 h-3 rounded-full shrink-0"
                                        :style="{ backgroundColor: folder.color }"
                                    />
                                    <Folder v-else class="w-4 h-4 shrink-0 text-secondary" :stroke-width="1.5" />
                                    <span class="flex-1 text-sm font-medium text-primary truncate">{{ folder.name }}</span>
                                    <span v-if="folderChildCounts[folder.id]" class="text-xs text-muted shrink-0">
                                        {{ folderChildCounts[folder.id] }} {{ t('vault.folders.sub_folders_count') }}
                                    </span>
                                    <span class="text-xs text-muted shrink-0 ml-2">
                                        {{ folderEntryCounts[folder.id] ?? 0 }} {{ t('vault.entries.count') }}
                                    </span>
                                    <ChevronDown v-if="expandedFolderIds.has(folder.id)" class="w-4 h-4 text-muted shrink-0 ml-1" :stroke-width="2" />
                                    <ChevronRight v-else class="w-4 h-4 text-muted shrink-0 ml-1" :stroke-width="2" />
                                </AppButton>

                                <div v-if="expandedFolderIds.has(folder.id)" class="border-t border-line/60">
                                    <div v-if="entriesInFolder(folder.id).length" class="divide-y divide-line/40">
                                        <VaultEntryRow
                                            v-for="entry in entriesInFolder(folder.id)"
                                            :key="entry.id"
                                            :entry="entry"
                                            :show-folder="false"
                                            class="rounded-none border-0"
                                            v-on:dragstart="onEntryDragStart($event, entry)"
                                            v-on:view="openView(entry)"
                                            v-on:edit="vaultForm.openEdit(entry, decryptedCache[entry.id] ?? {})"
                                            v-on:delete="confirmDelete(entry)"
                                            v-on:toggle-favorite="toggleFavorite(entry)"
                                        />
                                    </div>
                                    <p v-else class="px-4 py-3 text-sm text-muted">{{ t('vault.entries.emptyFolder') }}</p>
                                </div>
                            </div>
                        </div>
                        <AppNoData v-else :message="t('vault.folders.empty')" />
                    </template>

                    <template v-else-if="allView || showFavorites || (!allFoldersView && !allView)">
                        <div v-if="filteredEntries.length" class="space-y-1">
                            <VaultEntryRow
                                v-for="entry in filteredEntries"
                                :key="entry.id"
                                :entry="entry"
                                v-on:dragstart="onEntryDragStart($event, entry)"
                                v-on:view="openView(entry)"
                                v-on:edit="vaultForm.openEdit(entry, decryptedCache[entry.id] ?? {})"
                                v-on:delete="confirmDelete(entry)"
                                v-on:toggle-favorite="toggleFavorite(entry)"
                            />
                        </div>
                        <AppNoData v-else :message="emptyMessage" />
                    </template>
                </main>
            </div>
        </template>

        <VaultEntryFormModal
            v-model="vaultForm.createForm.value"
            :show="vaultForm.showCreate.value"
            mode="create"
            :errors="vaultForm.createErrors.value"
            :loading="vaultForm.createLoading.value"
            :folders="folders"
            v-on:close="vaultForm.showCreate.value = false"
            v-on:submit="vaultForm.submitCreate()"
            v-on:type-change="vaultForm.onCreateTypeChange"
        />

        <VaultEntryFormModal
            v-model="vaultForm.editForm.value"
            :show="vaultForm.showEdit.value"
            mode="edit"
            :errors="vaultForm.editErrors.value"
            :loading="vaultForm.editLoading.value"
            :folders="folders"
            :title="t('vault.entries.edit', { title: vaultForm.editingEntry.value?.title ?? '' })"
            v-on:close="vaultForm.showEdit.value = false"
            v-on:submit="vaultForm.submitEdit()"
            v-on:type-change="vaultForm.onEditTypeChange"
        />

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t('vault.entries.deleteConfirm', { title: pendingDelete?.title ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('vault.entries.deleteWarning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="entryDeleteLoading" v-on:click="doDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <VaultEntryViewModal
            :show="!!viewingEntry"
            :entry="viewingEntry"
            :decrypted-fields="viewingEntry ? (decryptedCache[viewingEntry.id] ?? {}) : {}"
            v-on:close="closeView"
        />

        <AppModal
            :show="folderModal.open"
            :title="folderModal.entity ? t('vault.folders.edit') : t('vault.folders.create')"
            max-width="sm"
            :closeable="false"
            v-on:close="folderModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitFolder">
                <AppInput
                    v-model="folderForm.name"
                    :label="t('vault.folders.name')"
                    :placeholder="t('vault.folders.namePlaceholder')"
                    :error="folderErrors.name"
                    required
                />
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary">{{ t('vault.folders.color') }}</span>
                        <AppToggle v-model="folderForm.useColor" />
                    </div>
                    <AppColorField
                        v-if="folderForm.useColor"
                        v-model="folderForm.color"
                    />
                </div>
                <AppMultiselect
                    v-model="folderForm.parentId"
                    :options="folderParentSelectOptions"
                    :label="t('vault.folders.parent')"
                    :placeholder="t('vault.folders.noParent')"
                    :allow-empty="true"
                    open-direction="top"
                        :use-teleport="false"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="folderModal.open = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}
                    </AppButton>
                    <AppButton variant="primary" size="md" :loading="folderSaving" v-on:click="submitFolder">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!pendingFolderDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingFolderDelete = null"
        >
            <p class="text-sm text-primary">{{ t('vault.folders.deleteConfirm', { name: pendingFolderDelete?.name ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('vault.folders.deleteWarning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingFolderDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDeleteFolder">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
        <VaultDestroyModal
            :show="destroyVault.show.value"
            :master-password="destroyVault.masterPassword.value"
            :loading="destroyVault.loading.value"
            :error="destroyVault.error.value"
            v-on:close="destroyVault.close()"
            v-on:update:master-password="destroyVault.masterPassword.value = $event"
            v-on:destroy="destroyVault.destroy()"
        />

        <VaultChangeMasterPasswordModal
            :show="changePwd.show.value"
            :step="changePwd.step.value"
            :current-password="changePwd.currentPassword.value"
            :new-password="changePwd.newPassword.value"
            :confirm-password="changePwd.confirmPassword.value"
            :progress="changePwd.progress.value"
            :errors="changePwd.errors.value"
            v-on:close="changePwd.close()"
            v-on:update:current-password="changePwd.currentPassword.value = $event"
            v-on:update:new-password="changePwd.newPassword.value = $event"
            v-on:update:confirm-password="changePwd.confirmPassword.value = $event"
            v-on:verify="changePwd.verifyCurrentPassword()"
            v-on:change="changePwd.changePassword()"
        />
    </div>
</template>
