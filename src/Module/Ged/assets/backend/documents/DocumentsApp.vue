<script setup>
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useDocumentsForm, DOCUMENT_STATUS_BADGE } from "./composables/useDocumentsForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppSelect from "@/shared/components/form/select/AppSelect.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppColorPicker from "@/shared/components/form/picker/AppColorPicker.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppFileInput from "@/shared/components/form/file/AppFileInput.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useFileSize } from "@/shared/composables/format/useFileSize.js";
import { useDocumentFilters } from "./composables/useDocumentFilters.js";
import { useDocumentDetail } from "./composables/useDocumentDetail.js";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import { Plus, Eye, Pencil, Trash2, Save, FileText, Paperclip, Upload, X, Folder, Download } from "lucide-vue-next";
import AppImagePreview from "@/shared/components/display/AppImagePreview.vue";
import AppThumbnail from "@/shared/components/display/AppThumbnail.vue";

const { t } = useI18n();
const { can } = usePrivileges();
const { formatDate } = useDateFormat();
const { formatSize } = useFileSize();
const props = defineProps({
    documents: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    folders: { type: Array, default: () => [] },
    search: { type: String, default: "" },
    showPath: { type: String, default: "" },
    versionsPath: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
    uploadPath: { type: String, required: true },
});

const categoryOptions = props.categories.map((c) => ({ value: c.id, label: c.name }));
const tagOptions = props.tags.map((tag) => ({ value: tag.id, label: tag.name }));
const folderOptions = props.folders.map((f) => ({ value: f.id, label: f.name }));

// ── Detail modal ─────────────────────────────────────────────────────────────
const { viewingDoc, viewingDocVersions, viewDoc, closeDetail } = useDocumentDetail(props.versionsPath);

function openEditFromDetail() {
    const doc = viewingDoc.value;
    closeDetail();
    openEdit(doc);
}

// ── Filters ──────────────────────────────────────────────────────────────────
const {
    filterCategoryId, filterTagId, filterFolderId, filterStatus,
    hasActiveFilter, extraParams, applyFilter, resetFilters,
} = useDocumentFilters(() => reset());

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.documents,
        extraParams,
    },
);

const {
    statusOptions,
    showCreate, newDoc, uploadingCreate, createErrors, createLoading, openCreate, onLocalFileCreate, submitCreate,
    showEdit, editingDoc, editForm, uploadingEdit, editErrors, editLoading, openEdit, onLocalFileEdit, submitEdit,
    pendingDelete, deleteLoading, confirmDelete, doDelete,
} = useDocumentsForm(props.createPath, props.updatePath, props.deletePath, reset, props.uploadPath);
</script>

<template>
    <div class="space-y-4">
        <!-- Search + add -->
        <AppListToolbar>
            <AppSearchInput v-model="searchInput" :placeholder="t('backend.ged.documents.searchPlaceholder')" v-on:search="onSearch" />
            <template #actions>
                <AppButton
                    v-if="can('ged.documents.create')"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openCreate"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" /> {{ t("backend.ged.documents.add") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <!-- Filters -->
        <div v-if="categories.length || tags.length || folders.length" class="flex flex-wrap gap-2">
            <AppMultiselect
                v-if="categories.length"
                v-model="filterCategoryId"
                :options="categoryOptions"
                :allow-empty="true"
                :placeholder="t('backend.ged.documents.filterByCategory')"
                class="min-w-44"
                v-on:update:model-value="applyFilter"
            />
            <AppMultiselect
                v-if="tags.length"
                v-model="filterTagId"
                :options="tagOptions"
                :allow-empty="true"
                :placeholder="t('backend.ged.documents.filterByTag')"
                class="min-w-44"
                v-on:update:model-value="applyFilter"
            />
            <AppMultiselect
                v-if="folders.length"
                v-model="filterFolderId"
                :options="folderOptions"
                :allow-empty="true"
                :placeholder="t('backend.ged.documents.filterByFolder')"
                class="min-w-44"
                v-on:update:model-value="applyFilter"
            />
            <AppMultiselect
                v-model="filterStatus"
                :options="statusOptions"
                :allow-empty="true"
                :searchable="false"
                :placeholder="t('backend.ged.documents.filterByStatus')"
                class="min-w-44"
                v-on:update:model-value="applyFilter"
            />
            <AppButton
                v-if="hasActiveFilter"
                variant="ghost"
                size="sm"
                v-on:click="resetFilters"
            >
                <X class="w-3 h-3" :stroke-width="2" /> {{ t("shared.common.reset") }}
            </AppButton>
        </div>

        <div class="relative space-y-4">
            <!-- Mobile cards -->
            <div class="sm:hidden space-y-2">
                <AppNoData v-if="!items?.length" :message="t('backend.ged.documents.empty')" />
                <div v-for="doc in items" :key="doc.id" class="bg-surface border border-line/60 rounded-xl overflow-hidden shadow-sm">
                    <div class="flex items-start gap-3 p-4">
                        <!-- Thumbnail or file icon -->
                        <div class="shrink-0 mt-0.5">
                            <AppThumbnail
                                v-if="doc.thumbnailUrl"
                                :src="doc.thumbnailUrl"
                                :alt="doc.fileName"
                                size="sm"
                            />
                            <div v-else-if="doc.fileMime === 'application/pdf'" class="w-8 h-8 flex items-center justify-center rounded border border-line/60 bg-surface-2">
                                <FileText class="w-4 h-4 text-rose-400" :stroke-width="1.5" />
                            </div>
                            <div v-else-if="doc.fileUrl" class="w-8 h-8 flex items-center justify-center rounded border border-line/60 bg-surface-2">
                                <Paperclip class="w-4 h-4 text-muted" :stroke-width="1.5" />
                            </div>
                            <div v-else class="w-8 h-8 flex items-center justify-center rounded border border-line/60 bg-surface-2">
                                <FileText class="w-4 h-4 text-muted" :stroke-width="1.5" />
                            </div>
                        </div>
                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-primary text-sm truncate">{{ doc.title }}</p>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5">
                                <span v-if="doc.reference" class="text-xs text-muted font-mono">{{ doc.reference }}</span>
                                <span v-if="doc.folderName" class="text-xs text-muted flex items-center gap-0.5">
                                    <Folder class="w-3 h-3" :stroke-width="2" /> {{ doc.folderName }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                <AppBadge :color="DOCUMENT_STATUS_BADGE[doc.status]">{{ doc.statusLabel }}</AppBadge>
                                <span v-if="doc.categoryName" class="text-xs text-muted">{{ doc.categoryName }}</span>
                            </div>
                            <div v-if="doc.tags?.length" class="flex flex-wrap gap-1 mt-1.5">
                                <span
                                    v-for="tag in doc.tags"
                                    :key="tag.id"
                                    class="inline-flex items-center text-xs px-1.5 py-0.5 rounded-full border border-line/60"
                                    :style="tag.color ? { backgroundColor: tag.color + '22', borderColor: tag.color + '66', color: tag.color } : {}"
                                >{{ tag.name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end px-3 py-2 border-t border-line/40 bg-surface-2/40">
                        <AppIconButton color="default" :title="t('shared.common.view')" v-on:click="viewDoc(doc)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton
                            v-if="doc.fileUrl"
                            color="default"
                            :title="t('shared.common.download')"
                            :href="doc.fileUrl"
                            download
                        >
                            <Download class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton v-if="can('ged.documents.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(doc)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('ged.documents.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(doc)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.ged.documents.title") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.ged.documents.category") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.ged.documents.status") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.ged.documents.file") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden xl:table-cell">{{ t("backend.ged.documents.preview") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="doc in items" :key="doc.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-medium text-primary">{{ doc.title }}</p>
                                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                    <span v-if="doc.reference" class="text-xs text-muted font-mono">{{ doc.reference }}</span>
                                    <span v-if="doc.folderName" class="text-xs text-muted flex items-center gap-0.5">
                                        <Folder class="w-3 h-3" :stroke-width="2" /> {{ doc.folderName }}
                                    </span>
                                    <span
                                        v-for="tag in doc.tags"
                                        :key="tag.id"
                                        class="inline-flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-full border border-line/60"
                                        :style="tag.color ? { backgroundColor: tag.color + '22', borderColor: tag.color + '66', color: tag.color } : {}"
                                    >
                                        {{ tag.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ doc.categoryName ?? t("backend.ged.documents.noCategory") }}</td>
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <AppBadge :color="DOCUMENT_STATUS_BADGE[doc.status]">{{ doc.statusLabel }}</AppBadge>
                            </td>
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <span v-if="doc.fileName" class="flex items-center gap-1 text-xs text-muted"><Paperclip class="w-3 h-3" :stroke-width="2" /> {{ doc.fileName }}</span>
                                <span v-else class="text-muted text-xs">—</span>
                            </td>
                            <td class="px-6 py-3 hidden xl:table-cell">
                                <AppThumbnail
                                    v-if="doc.thumbnailUrl"
                                    :src="doc.thumbnailUrl"
                                    :alt="doc.fileName"
                                    size="landscape"
                                />
                                <div v-else-if="doc.fileMime === 'application/pdf'" class="flex items-center gap-1.5 text-xs text-muted">
                                    <FileText class="w-5 h-5 shrink-0 text-rose-400" :stroke-width="1.5" /> PDF
                                </div>
                                <div v-else-if="doc.fileUrl" class="flex items-center gap-1.5 text-xs text-muted">
                                    <FileText class="w-5 h-5 shrink-0" :stroke-width="1.5" /> {{ doc.fileMime ?? '—' }}
                                </div>
                                <span v-else class="text-muted text-xs">—</span>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton color="default" :title="t('shared.common.view')" v-on:click="viewDoc(doc)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton
                                        v-if="doc.fileUrl"
                                        color="default"
                                        :title="t('shared.common.download')"
                                        :href="doc.fileUrl"
                                        download
                                    >
                                        <Download class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="can('ged.documents.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(doc)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton v-if="can('ged.documents.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(doc)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items?.length">
                            <td :colspan="6"><AppNoData :message="t('backend.ged.documents.empty')" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />
            <AppLoader :active="loading" />
        </div>

        <!-- Create modal -->
        <AppModal
            :show="showCreate"
            :title="t('backend.ged.documents.create')"
            :icon="FileText"
            :closeable="false"
            v-on:close="showCreate = false"
        >
            <div class="space-y-4">
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
                    v-if="tags.length"
                    v-model="newDoc.tagIds"
                    :label="t('backend.ged.documents.tags')"
                    :options="tagOptions"
                    :multiple="true"
                    :allow-empty="true"
                    :placeholder="t('backend.ged.documents.noTags')"
                />
                <AppMultiselect
                    v-if="folders.length"
                    v-model="newDoc.folderId"
                    :label="t('backend.ged.documents.folder')"
                    :options="folderOptions"
                    :allow-empty="true"
                    :placeholder="t('backend.ged.documents.noFolder')"
                />
                <AppMultiselect
                    v-model="newDoc.status"
                    :label="t('backend.ged.documents.status')"
                    :options="statusOptions"
                    :allow-empty="false"
                    :searchable="false"
                />
                <div class="flex items-center gap-2 flex-wrap">
                    <AppFileInput v-on:change="onLocalFileCreate">
                        <template #default="{ trigger }">
                            <AppButton
                                variant="ghost"
                                size="sm"
                                type="button"
                                :loading="uploadingCreate"
                                v-on:click="trigger"
                            >
                                <Upload class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.ged.documents.chooseFile") }}
                            </AppButton>
                        </template>
                    </AppFileInput>
                    <span v-if="newDoc.originalName ?? newDoc.fileName" class="text-sm text-muted flex items-center gap-1"><FileText class="w-4 h-4" :stroke-width="2" /> {{ newDoc.originalName ?? newDoc.fileName }}</span>
                </div>
            </div>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showCreate = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" :loading="createLoading" v-on:click="submitCreate"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
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
            <div class="space-y-4">
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
                    v-if="tags.length"
                    v-model="editForm.tagIds"
                    :label="t('backend.ged.documents.tags')"
                    :options="tagOptions"
                    :multiple="true"
                    :allow-empty="true"
                    :placeholder="t('backend.ged.documents.noTags')"
                />
                <AppMultiselect
                    v-if="folders.length"
                    v-model="editForm.folderId"
                    :label="t('backend.ged.documents.folder')"
                    :options="folderOptions"
                    :allow-empty="true"
                    :placeholder="t('backend.ged.documents.noFolder')"
                />
                <AppMultiselect
                    v-model="editForm.status"
                    :label="t('backend.ged.documents.status')"
                    :options="statusOptions"
                    :allow-empty="false"
                    :searchable="false"
                />
                <div class="flex items-center gap-2 flex-wrap">
                    <AppFileInput v-on:change="onLocalFileEdit">
                        <template #default="{ trigger }">
                            <AppButton
                                variant="ghost"
                                size="sm"
                                type="button"
                                :loading="uploadingEdit"
                                v-on:click="trigger"
                            >
                                <Upload class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.ged.documents.chooseFile") }}
                            </AppButton>
                        </template>
                    </AppFileInput>
                    <span v-if="editForm.fileName" class="text-sm text-muted flex items-center gap-1"><FileText class="w-4 h-4" :stroke-width="2" /> {{ editForm.fileName }}</span>
                </div>
            </div>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" :loading="editLoading" v-on:click="submitEdit"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
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

        <!-- Detail modal -->
        <AppModal
            :show="!!viewingDoc"
            :title="viewingDoc?.title ?? ''"
            :icon="FileText"
            :closeable="false"
            v-on:close="viewingDoc = null"
        >
            <template v-if="viewingDoc">
                <div class="space-y-4">
                    <!-- Status + reference -->
                    <div class="flex items-center gap-3 flex-wrap">
                        <AppBadge :color="DOCUMENT_STATUS_BADGE[viewingDoc.status]">{{ viewingDoc.statusLabel }}</AppBadge>
                        <span v-if="viewingDoc.reference" class="text-xs text-muted font-mono">{{ viewingDoc.reference }}</span>
                    </div>

                    <!-- Description -->
                    <p v-if="viewingDoc.description" class="text-sm text-secondary leading-relaxed">{{ viewingDoc.description }}</p>

                    <!-- Metadata -->
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div v-if="viewingDoc.categoryName">
                            <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("backend.ged.documents.category") }}</dt>
                            <dd class="text-primary">{{ viewingDoc.categoryName }}</dd>
                        </div>
                        <div v-if="viewingDoc.folderName">
                            <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("backend.ged.documents.folder") }}</dt>
                            <dd class="text-primary flex items-center gap-1"><Folder class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" /> {{ viewingDoc.folderName }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("shared.common.created") }}</dt>
                            <dd class="text-secondary">{{ formatDate(viewingDoc.createdAt) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("shared.common.updated") }}</dt>
                            <dd class="text-secondary">{{ formatDate(viewingDoc.updatedAt) }}</dd>
                        </div>
                    </dl>

                    <!-- Tags -->
                    <div v-if="viewingDoc.tags?.length" class="flex flex-wrap gap-1.5">
                        <span
                            v-for="tag in viewingDoc.tags"
                            :key="tag.id"
                            class="inline-flex items-center text-xs px-2 py-0.5 rounded-full border"
                            :style="tag.color ? { backgroundColor: tag.color + '22', borderColor: tag.color + '66', color: tag.color } : {}"
                        >{{ tag.name }}</span>
                    </div>

                    <!-- Version history -->
                    <div v-if="viewingDocVersions.length > 1" class="space-y-2">
                        <p class="text-xs text-muted uppercase tracking-wide">{{ t("backend.ged.documents.versions") }}</p>
                        <div class="divide-y divide-line/40 rounded-lg border border-line overflow-hidden">
                            <div
                                v-for="version in viewingDocVersions"
                                :key="version.id"
                                class="flex items-center gap-3 px-3 py-2 text-sm"
                                :class="version.versionNumber === viewingDocVersions[0].versionNumber ? 'bg-accent/5' : 'bg-surface'"
                            >
                                <span class="shrink-0 text-xs font-mono font-medium px-1.5 py-0.5 rounded bg-surface-2 text-secondary">v{{ version.versionNumber }}</span>
                                <span class="flex-1 truncate text-primary text-xs">{{ version.fileName }}</span>
                                <span class="text-xs text-muted shrink-0">{{ formatDate(version.createdAt) }}</span>
                                <a :href="version.fileUrl" target="_blank" download class="shrink-0 text-xs text-accent hover:underline flex items-center gap-0.5">
                                    <Download class="w-3 h-3" :stroke-width="2" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- File -->
                    <div v-if="viewingDoc.fileUrl" class="rounded-lg border border-line overflow-hidden">
                        <template v-if="viewingDoc.fileMime?.startsWith('image/')">
                            <AppImagePreview :src="viewingDoc.fileUrl" :alt="viewingDoc.fileName" full />
                            <div class="flex justify-end px-3 py-2 border-t border-line bg-surface">
                                <a :href="viewingDoc.fileUrl" download class="flex items-center gap-1 text-xs text-accent hover:underline">
                                    <Download class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.download") }}
                                </a>
                            </div>
                        </template>
                        <template v-else-if="viewingDoc.fileMime === 'application/pdf'">
                            <iframe
                                :src="viewingDoc.fileUrl"
                                class="w-full h-64"
                                :title="viewingDoc.fileName"
                            />
                            <div class="flex justify-end px-3 py-2 border-t border-line bg-surface">
                                <a :href="viewingDoc.fileUrl" download class="flex items-center gap-1 text-xs text-accent hover:underline">
                                    <Download class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.download") }}
                                </a>
                            </div>
                        </template>
                        <div v-else class="flex items-center gap-3 px-4 py-3 bg-surface-2">
                            <FileText class="w-6 h-6 text-muted shrink-0" :stroke-width="1.5" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-primary truncate">{{ viewingDoc.fileName }}</p>
                                <p v-if="viewingDoc.fileSize" class="text-xs text-muted">{{ formatSize(viewingDoc.fileSize) }}</p>
                            </div>
                            <a :href="viewingDoc.fileUrl" target="_blank" download class="text-xs text-accent hover:underline flex items-center gap-1 shrink-0">
                                <Download class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.download") }}
                            </a>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="viewingDoc = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.close") }}</AppButton>
                    <AppButton
                        v-if="viewingDoc?.fileUrl"
                        variant="secondary"
                        size="md"
                        :href="viewingDoc.fileUrl"
                        download
                    >
                        <Download class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.download") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
