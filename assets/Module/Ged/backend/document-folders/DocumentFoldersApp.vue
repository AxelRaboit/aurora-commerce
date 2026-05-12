<script setup>
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useDocumentFoldersForm } from "./composables/useDocumentFoldersForm.js";
import { buildFolderTree, flattenFolders } from "@/shared/utils/tree/folderTree.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { Plus, Pencil, Trash2, Save, X, Folder, ChevronRight } from "lucide-vue-next";

const { t } = useI18n();
const { can } = usePrivileges();
const props = defineProps({
    folders: { type: Array, default: () => [] },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const {
    items,
    showCreate, newFolder, createErrors, createLoading, openCreate, submitCreate,
    showEdit, editingFolder, editForm, editErrors, editLoading, openEdit, submitEdit,
    pendingDelete, deleteLoading, confirmDelete, doDelete,
} = useDocumentFoldersForm(props.folders, props.createPath, props.updatePath, props.deletePath);

const parentOptions = computed(() => [
    { value: null, label: t("backend.ged.folders.noParent") },
    ...items.value.map((folder) => ({ value: folder.id, label: folder.name })),
]);

const collapsedIds = ref(new Set());

function toggleCollapse(id) {
    const next = new Set(collapsedIds.value);
    if (next.has(id)) { next.delete(id); } else { next.add(id); }
    collapsedIds.value = next;
}

const flatTree = computed(() => {
    const tree = buildFolderTree(items.value);
    return flattenFolders(tree, 0, collapsedIds.value);
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex justify-end">
            <AppButton
                v-if="can('ged.folders.manage')"
                variant="primary"
                size="md"
                v-on:click="openCreate"
            >
                <Plus class="w-4 h-4" :stroke-width="2" /> {{ t("backend.ged.folders.add") }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line rounded-lg overflow-hidden">
            <div v-if="!items?.length" class="px-6 py-4">
                <AppNoData :message="t('backend.ged.folders.empty')" />
            </div>
            <div v-else class="divide-y divide-line/40">
                <div
                    v-for="node in flatTree"
                    :key="node.id"
                    class="group flex items-center gap-1 pr-3 hover:bg-surface-2/40 transition-colors"
                    :style="{ paddingLeft: `${0.75 + node.depth * 1.25}rem` }"
                >
                    <!-- Collapse toggle -->
                    <button
                        v-if="node.childCount"
                        type="button"
                        class="shrink-0 p-0.5 text-muted hover:text-primary transition-colors"
                        v-on:click="toggleCollapse(node.id)"
                    >
                        <ChevronRight
                            class="w-3.5 h-3.5 transition-transform duration-150"
                            :class="collapsedIds.has(node.id) ? '' : 'rotate-90'"
                            :stroke-width="2"
                        />
                    </button>
                    <span v-else class="w-4 shrink-0" />

                    <Folder class="w-4 h-4 text-muted shrink-0" :stroke-width="1.5" />

                    <span class="flex-1 py-3 text-sm font-medium text-primary truncate">{{ node.name }}</span>

                    <span v-if="node.childCount" class="text-xs text-muted tabular-nums shrink-0">{{ node.childCount }}</span>

                    <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity ml-2">
                        <AppIconButton v-if="can('ged.folders.manage')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(node)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('ged.folders.manage')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(node)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>
        </div>

        <AppModal
            :show="showCreate"
            :title="t('backend.ged.folders.create')"
            :icon="Folder"
            :closeable="false"
            :scrollable="false"
            v-on:close="showCreate = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="newFolder.name"
                    :label="t('backend.ged.folders.name')"
                    :placeholder="t('backend.ged.folders.namePlaceholder')"
                    :error="createErrors.name"
                    required
                />
                <AppMultiselect
                    v-model="newFolder.parentId"
                    :label="t('backend.ged.folders.parent')"
                    :options="parentOptions"
                    :allow-empty="true"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" :loading="createLoading" v-on:click="submitCreate"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="showEdit"
            :title="t('backend.ged.folders.edit', { name: editingFolder?.name ?? '' })"
            :icon="Pencil"
            :closeable="false"
            :scrollable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('backend.ged.folders.name')"
                    :placeholder="t('backend.ged.folders.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <AppMultiselect
                    v-model="editForm.parentId"
                    :label="t('backend.ged.folders.parent')"
                    :options="parentOptions.filter((opt) => opt.value !== editingFolder?.id)"
                    :allow-empty="true"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" :loading="editLoading" v-on:click="submitEdit"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t("backend.ged.folders.deleteConfirm", { name: pendingDelete?.name ?? "" }) }}</p>
            <p class="text-sm text-secondary">{{ t("backend.ged.folders.deleteWarning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
