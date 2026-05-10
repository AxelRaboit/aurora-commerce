<script setup>
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useDocumentsForm, DOCUMENT_STATUS_BADGE } from "./composables/useDocumentsForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import MediaPickerModal from "@core/backend/media/MediaPickerModal.vue";
import { Plus, Pencil, Trash2, Save, FileText, Paperclip, X } from "lucide-vue-next";

const { t } = useI18n();
const { can } = usePrivileges();
const props = defineProps({
    documents: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
    mediaPickerPath: { type: String, default: "" },
});

const categoryOptions = props.categories.map((c) => ({ value: c.id, label: c.name }));

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath, { initialSearch: props.search, initialData: props.documents },
);

const {
    statusOptions,
    showCreate, newDoc, showMediaPickerCreate, createErrors, createLoading, openCreate, onFilePickedCreate, submitCreate,
    showEdit, editingDoc, editForm, showMediaPickerEdit, editErrors, editLoading, openEdit, onFilePickedEdit, submitEdit,
    pendingDelete, deleteLoading, confirmDelete, doDelete,
} = useDocumentsForm(props.createPath, props.updatePath, props.deletePath, reset);
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput v-model="searchInput" :placeholder="t('backend.ged.documents.searchPlaceholder')" v-on:search="onSearch" />
            <AppButton
                v-if="can('ged.documents.manage')"
                variant="primary"
                size="md"
                class="w-full sm:w-auto"
                v-on:click="openCreate"
            >
                <Plus class="w-4 h-4" :stroke-width="2" /> {{ t("backend.ged.documents.add") }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.ged.documents.title") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.ged.documents.category") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.ged.documents.status") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.ged.documents.file") }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="doc in items" :key="doc.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3">
                            <p class="font-medium text-primary">{{ doc.title }}</p>
                            <p v-if="doc.reference" class="text-xs text-muted font-mono">{{ doc.reference }}</p>
                        </td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ doc.categoryName ?? t("backend.ged.documents.noCategory") }}</td>
                        <td class="px-6 py-3 hidden lg:table-cell">
                            <AppBadge :color="DOCUMENT_STATUS_BADGE[doc.status]">{{ doc.statusLabel }}</AppBadge>
                        </td>
                        <td class="px-6 py-3 hidden lg:table-cell">
                            <span v-if="doc.fileName" class="flex items-center gap-1 text-xs text-muted"><Paperclip class="w-3 h-3" :stroke-width="2" /> {{ doc.fileName }}</span>
                            <span v-else class="text-muted text-xs">—</span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="can('ged.documents.manage')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(doc)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ged.documents.manage')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(doc)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td :colspan="5"><AppNoData :message="t('backend.ged.documents.empty')" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <!-- Create modal -->
        <AppModal
            :show="showCreate"
            :title="t('backend.ged.documents.create')"
            :icon="FileText"
            :closeable="false"
            v-on:close="showCreate = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="newDoc.title"
                    :label="t('backend.ged.documents.title')"
                    :placeholder="t('backend.ged.documents.titlePlaceholder')"
                    :error="createErrors.title"
                    required
                />
                <AppInput v-model="newDoc.description" :label="t('backend.ged.documents.description')" :placeholder="t('backend.ged.documents.descriptionPlaceholder')" />
                <AppMultiselect
                    v-model="newDoc.categoryId"
                    :label="t('backend.ged.documents.category')"
                    :options="categoryOptions"
                    :allow-empty="true"
                    :placeholder="t('backend.ged.documents.noCategory')"
                />
                <AppMultiselect
                    v-model="newDoc.status"
                    :label="t('backend.ged.documents.status')"
                    :options="statusOptions"
                    :allow-empty="false"
                    :searchable="false"
                />
                <div class="flex items-center gap-3">
                    <AppButton variant="ghost" size="sm" type="button" v-on:click="showMediaPickerCreate = true">
                        <Paperclip class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.ged.documents.chooseFile") }}
                    </AppButton>
                    <span v-if="newDoc.fileName" class="text-sm text-muted flex items-center gap-1"><FileText class="w-4 h-4" :stroke-width="2" /> {{ newDoc.fileName }}</span>
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Edit modal -->
        <AppModal
            :show="showEdit"
            :title="t('backend.ged.documents.edit', { title: editingDoc?.title ?? '' })"
            :icon="Pencil"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.title"
                    :label="t('backend.ged.documents.title')"
                    :placeholder="t('backend.ged.documents.titlePlaceholder')"
                    :error="editErrors.title"
                    required
                />
                <AppInput v-model="editForm.description" :label="t('backend.ged.documents.description')" :placeholder="t('backend.ged.documents.descriptionPlaceholder')" />
                <AppMultiselect
                    v-model="editForm.categoryId"
                    :label="t('backend.ged.documents.category')"
                    :options="categoryOptions"
                    :allow-empty="true"
                    :placeholder="t('backend.ged.documents.noCategory')"
                />
                <AppMultiselect
                    v-model="editForm.status"
                    :label="t('backend.ged.documents.status')"
                    :options="statusOptions"
                    :allow-empty="false"
                    :searchable="false"
                />
                <div class="flex items-center gap-3">
                    <AppButton variant="ghost" size="sm" type="button" v-on:click="showMediaPickerEdit = true">
                        <Paperclip class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.ged.documents.chooseFile") }}
                    </AppButton>
                    <span v-if="editForm.fileName" class="text-sm text-muted flex items-center gap-1"><FileText class="w-4 h-4" :stroke-width="2" /> {{ editForm.fileName }}</span>
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete modal -->
        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t("backend.ged.documents.deleteConfirm", { title: pendingDelete?.title ?? "" }) }}</p>
            <p class="text-sm text-secondary">{{ t("backend.ged.documents.deleteWarning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Media pickers -->
        <MediaPickerModal :show="showMediaPickerCreate" :list-path="mediaPickerPath" v-on:close="showMediaPickerCreate = false" v-on:select="onFilePickedCreate" />
        <MediaPickerModal :show="showMediaPickerEdit" :list-path="mediaPickerPath" v-on:close="showMediaPickerEdit = false" v-on:select="onFilePickedEdit" />
    </div>
</template>
