<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useDocumentFoldersForm } from "./composables/useDocumentFoldersForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { Plus, Pencil, Trash2, Save, X, Folder } from "lucide-vue-next";

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

        <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.ged.folders.name") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.ged.folders.parent") }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="folder in items" :key="folder.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3 font-medium text-primary flex items-center gap-2">
                            <Folder class="w-4 h-4 text-muted flex-shrink-0" :stroke-width="1.5" />
                            {{ folder.name }}
                        </td>
                        <td class="px-6 py-3 text-muted hidden md:table-cell">
                            {{ items.find((f) => f.id === folder.parentId)?.name ?? '—' }}
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="can('ged.folders.manage')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(folder)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ged.folders.manage')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(folder)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td :colspan="4"><AppNoData :message="t('backend.ged.folders.empty')" /></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppModal
            :show="showCreate"
            :title="t('backend.ged.folders.create')"
            :icon="Folder"
            :closeable="false"
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
