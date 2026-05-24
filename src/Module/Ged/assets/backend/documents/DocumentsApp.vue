<script setup>
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useQrCode } from "@/shared/composables/overlay/useQrCode.js";
import { useClipboard } from "@/shared/composables/useClipboard.js";
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
import AppQrCodeModal from "@/shared/components/overlay/AppQrCodeModal.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppFileInput from "@/shared/components/form/file/AppFileInput.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useFileSize } from "@/shared/composables/format/useFileSize.js";
import { useDocumentFilters } from "./composables/useDocumentFilters.js";
import { useDocumentDetail } from "./composables/useDocumentDetail.js";
import { useDocumentsDisplay, DOCUMENT_SORT_FIELDS } from "./composables/useDocumentsDisplay.js";
import { useMultiSelection } from "@/shared/composables/list/useMultiSelection.js";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import { Plus, Eye, Pencil, Trash2, Save, FileText, Paperclip, Upload, X, Folder, Download, QrCode, LayoutGrid, List, SortAsc, SortDesc, CheckSquare, Square, Copy } from "lucide-vue-next";
import AppImagePreview from "@/shared/components/display/AppImagePreview.vue";
import AppImage from "@/shared/components/display/AppImage.vue";
import AppThumbnail from "@/shared/components/display/AppThumbnail.vue";
import AppFilePreview from "@/shared/components/display/AppFilePreview.vue";
import AppOverlayIconButton from "@/shared/components/action/AppOverlayIconButton.vue";
import AppSelectionCheck from "@/shared/components/feedback/AppSelectionCheck.vue";
import DocumentTagChip from "@ged/backend/documents/components/DocumentTagChip.vue";

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
    bulkDeletePath: { type: String, default: "" },
    listPath: { type: String, required: true },
    uploadPath: { type: String, required: true },
});

const categoryOptions = props.categories.map((c) => ({ value: c.id, label: c.name }));
const tagOptions = props.tags.map((tag) => ({ value: tag.id, label: tag.name }));
const folderOptions = props.folders.map((f) => ({ value: f.id, label: f.name }));

// ── Detail modal ─────────────────────────────────────────────────────────────
const { viewingDoc, viewingDocVersions, viewDoc, closeDetail } = useDocumentDetail(props.versionsPath);

const { qrItem: qrDoc, openQr, closeQr } = useQrCode();
const { copy } = useClipboard();

function permalinkFor(doc) {
    return doc.permalink ?? (doc.fileUrl ? window.location.origin + doc.fileUrl : "");
}

function openEditFromDetail() {
    const doc = viewingDoc.value;
    closeDetail();
    openEdit(doc);
}

// ── Filters ──────────────────────────────────────────────────────────────────
const {
    filterCategoryId, filterTagId, filterFolderId, filterStatus, filterMimeGroup,
    hasActiveFilter, extraParams, applyFilter, resetFilters,
} = useDocumentFilters(() => reset());

const mimeGroupOptions = [
    { value: "image", label: t("backend.ged.documents.type_image") },
    { value: "video", label: t("backend.ged.documents.type_video") },
    { value: "pdf", label: t("backend.ged.documents.type_pdf") },
    { value: "other", label: t("backend.ged.documents.type_other") },
];

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

const { viewMode, setViewMode, sortBy, sortDir, setSort, displayedItems } = useDocumentsDisplay(items);
const { selectedIds, isSelecting, toggle: toggleSelect, clear: clearSelection } = useMultiSelection();

async function doBulkDelete() {
    if (!props.bulkDeletePath || selectedIds.value.size === 0) return;
    const ids = [...selectedIds.value];
    const res = await fetch(props.bulkDeletePath, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
        body: JSON.stringify({ ids }),
    });
    if (!res.ok) return;
    clearSelection();
    isSelecting.value = false;
    await reset();
}
</script>

<template>
    <div class="space-y-4">
        <!-- Search + add -->
        <AppListToolbar>
            <AppSearchInput v-model="searchInput" :placeholder="t('backend.ged.documents.search_placeholder')" v-on:search="onSearch" />
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
                :placeholder="t('backend.ged.documents.filter_by_category')"
                class="min-w-44"
                v-on:update:model-value="applyFilter"
            />
            <AppMultiselect
                v-if="tags.length"
                v-model="filterTagId"
                :options="tagOptions"
                :allow-empty="true"
                :placeholder="t('backend.ged.documents.filter_by_tag')"
                class="min-w-44"
                v-on:update:model-value="applyFilter"
            />
            <AppMultiselect
                v-if="folders.length"
                v-model="filterFolderId"
                :options="folderOptions"
                :allow-empty="true"
                :placeholder="t('backend.ged.documents.filter_by_folder')"
                class="min-w-44"
                v-on:update:model-value="applyFilter"
            />
            <AppMultiselect
                v-model="filterStatus"
                :options="statusOptions"
                :allow-empty="true"
                :searchable="false"
                :placeholder="t('backend.ged.documents.filter_by_status')"
                class="min-w-44"
                v-on:update:model-value="applyFilter"
            />
            <AppMultiselect
                v-model="filterMimeGroup"
                :options="mimeGroupOptions"
                :allow-empty="true"
                :searchable="false"
                :placeholder="t('backend.ged.documents.filter_by_type')"
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

        <!-- View toolbar: sort + view mode + multiselect -->
        <div class="flex flex-wrap items-center gap-1.5">
            <div class="flex gap-1 border border-line/60 rounded-lg p-0.5">
                <AppTab
                    v-for="s in DOCUMENT_SORT_FIELDS"
                    :key="s.key"
                    size="xs"
                    :active="sortBy === s.key"
                    :title="s.labelKey ? t(s.labelKey) : s.label"
                    v-on:click="setSort(s.key)"
                >
                    {{ s.labelKey ? t(s.labelKey) : s.label }}
                    <SortAsc v-if="sortBy === s.key && sortDir === 'asc'" class="w-3 h-3" :stroke-width="2" />
                    <SortDesc v-else-if="sortBy === s.key" class="w-3 h-3" :stroke-width="2" />
                </AppTab>
            </div>
            <div class="flex border border-line/60 rounded-lg p-0.5">
                <AppIconButton
                    size="sm"
                    variant="ghost"
                    :class="viewMode === 'grid' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'"
                    v-on:click="setViewMode('grid')"
                >
                    <LayoutGrid class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton
                    size="sm"
                    variant="ghost"
                    :class="viewMode === 'list' ? 'bg-surface-3 text-primary' : 'text-muted hover:text-primary'"
                    v-on:click="setViewMode('list')"
                >
                    <List class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
            </div>
            <AppIconButton
                v-if="can('ged.documents.delete')"
                size="sm"
                variant="ghost"
                class="border border-line/60"
                :class="isSelecting ? 'bg-accent-500/15 text-accent-400' : 'text-muted hover:text-primary'"
                v-on:click="isSelecting = !isSelecting; if (!isSelecting) clearSelection()"
            >
                <CheckSquare class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
            <AppButton
                v-if="isSelecting && selectedIds.size > 0"
                variant="danger"
                size="sm"
                class="ml-auto"
                v-on:click="doBulkDelete"
            >
                <Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }} ({{ selectedIds.size }})
            </AppButton>
        </div>

        <div class="relative space-y-4">
            <AppNoData v-if="!displayedItems?.length" :message="t('backend.ged.documents.empty')" />

            <!-- Grid view (Media-style cards on all sizes) -->
            <div v-if="viewMode === 'grid' && displayedItems?.length" class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                <div
                    v-for="doc in displayedItems"
                    :key="doc.id"
                    class="group relative bg-surface border rounded-lg overflow-hidden transition-colors cursor-pointer"
                    :class="[
                        selectedIds.has(doc.id) ? 'border-accent-400 ring-2 ring-accent-500' : 'border-line/60 hover:border-accent-400',
                    ]"
                    v-on:click="isSelecting ? toggleSelect(doc.id) : viewDoc(doc)"
                >
                    <div v-if="isSelecting" class="absolute top-1.5 left-1.5 z-10" v-on:click.stop="toggleSelect(doc.id)">
                        <AppSelectionCheck :active="selectedIds.has(doc.id)" />
                    </div>
                    <div class="relative aspect-square bg-surface-2 flex items-center justify-center overflow-hidden">
                        <AppImage
                            v-if="doc.thumbnailUrl"
                            :src="doc.thumbnailUrl"
                            :alt="doc.fileName ?? doc.title"
                            object-fit="cover"
                        />
                        <FileText v-else-if="doc.fileMime === 'application/pdf'" class="w-12 h-12 text-rose-400" :stroke-width="1.5" />
                        <Paperclip v-else-if="doc.fileUrl" class="w-10 h-10 text-muted" :stroke-width="1.5" />
                        <FileText v-else class="w-10 h-10 text-muted" :stroke-width="1.5" />
                        <AppBadge v-if="doc.status" :color="DOCUMENT_STATUS_BADGE[doc.status]" class="absolute top-1 right-1">{{ doc.statusLabel }}</AppBadge>
                        <div v-if="!isSelecting" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5">
                            <AppOverlayIconButton size="sm" variant="light" :title="t('shared.common.view')" v-on:click.stop="viewDoc(doc)">
                                <Eye class="w-4 h-4" :stroke-width="2" />
                            </AppOverlayIconButton>
                            <AppOverlayIconButton
                                v-if="can('ged.documents.edit')"
                                size="sm"
                                variant="light"
                                :title="t('shared.common.edit')"
                                v-on:click.stop="openEdit(doc)"
                            >
                                <Pencil class="w-4 h-4" :stroke-width="2" />
                            </AppOverlayIconButton>
                            <AppOverlayIconButton
                                v-if="doc.fileUrl"
                                size="sm"
                                variant="light"
                                :title="t('shared.common.qr_code')"
                                v-on:click.stop="openQr(doc)"
                            >
                                <QrCode class="w-4 h-4" :stroke-width="2" />
                            </AppOverlayIconButton>
                        </div>
                    </div>
                    <div class="p-2 space-y-1">
                        <div class="text-xs font-medium text-primary truncate" :title="doc.title">{{ doc.title }}</div>
                        <div v-if="doc.reference" class="text-xs text-muted font-mono truncate">{{ doc.reference }}</div>
                        <div class="text-xs text-muted">
                            <span v-if="doc.fileSize">{{ formatSize(doc.fileSize) }}</span>
                            <span v-if="doc.categoryName"><span v-if="doc.fileSize"> · </span>{{ doc.categoryName }}</span>
                        </div>
                        <div v-if="doc.folderName" class="text-xs text-accent-400/80 truncate flex items-center gap-1">
                            <Folder class="w-2.5 h-2.5 shrink-0" :stroke-width="2" />{{ doc.folderName }}
                        </div>
                        <div v-if="doc.tags?.length" class="flex flex-wrap gap-1 pt-0.5">
                            <DocumentTagChip v-for="tag in doc.tags" :key="tag.id" :tag="tag" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile cards (list view fallback on mobile) -->
            <div v-else-if="viewMode === 'list' && displayedItems?.length" class="sm:hidden space-y-2">
                <div
                    v-for="doc in displayedItems"
                    :key="doc.id"
                    class="bg-surface border border-line/60 rounded-xl overflow-hidden shadow-sm relative"
                    :class="{ 'ring-2 ring-accent-400': isSelecting && selectedIds.has(doc.id), 'cursor-pointer': isSelecting }"
                    v-on:click="isSelecting ? toggleSelect(doc.id) : null"
                >
                    <div v-if="isSelecting" class="absolute top-1.5 left-1.5 z-10 bg-surface/90 rounded p-0.5" v-on:click.stop="toggleSelect(doc.id)">
                        <CheckSquare v-if="selectedIds.has(doc.id)" class="w-4 h-4 text-accent-400" :stroke-width="2" />
                        <Square v-else class="w-4 h-4 text-muted" :stroke-width="2" />
                    </div>
                    <div class="flex items-start gap-3 p-4">
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
                                <span v-if="doc.fileSize" class="text-xs text-muted tabular-nums">{{ formatSize(doc.fileSize) }}</span>
                            </div>
                            <div v-if="doc.tags?.length" class="flex flex-wrap gap-1 mt-1.5">
                                <DocumentTagChip v-for="tag in doc.tags" :key="tag.id" :tag="tag" />
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
                        <AppIconButton v-if="doc.fileUrl" color="default" :title="t('shared.common.qr_code')" v-on:click="openQr(doc)"><QrCode class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('ged.documents.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(doc)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('ged.documents.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(doc)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>

            <!-- Desktop table (list view) -->
            <div v-show="viewMode === 'list'" class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th v-if="isSelecting" class="w-8 px-3 py-3" />
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.ged.documents.title") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.ged.documents.category") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.ged.documents.status") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.ged.documents.file") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("backend.ged.documents.size") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden xl:table-cell">{{ t("backend.ged.documents.preview") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr
                            v-for="doc in displayedItems"
                            :key="doc.id"
                            class="group hover:bg-surface-2/40 transition-colors"
                            :class="{ 'bg-accent-500/10': isSelecting && selectedIds.has(doc.id), 'cursor-pointer': isSelecting }"
                            v-on:click="isSelecting ? toggleSelect(doc.id) : null"
                        >
                            <td v-if="isSelecting" class="px-3 py-3" v-on:click.stop="toggleSelect(doc.id)">
                                <CheckSquare v-if="selectedIds.has(doc.id)" class="w-4 h-4 text-accent-400" :stroke-width="2" />
                                <Square v-else class="w-4 h-4 text-muted" :stroke-width="2" />
                            </td>
                            <td class="px-6 py-3">
                                <p class="font-medium text-primary">{{ doc.title }}</p>
                                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                    <span v-if="doc.reference" class="text-xs text-muted font-mono">{{ doc.reference }}</span>
                                    <span v-if="doc.folderName" class="text-xs text-muted flex items-center gap-0.5">
                                        <Folder class="w-3 h-3" :stroke-width="2" /> {{ doc.folderName }}
                                    </span>
                                    <DocumentTagChip v-for="tag in doc.tags" :key="tag.id" :tag="tag" />
                                </div>
                            </td>
                            <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ doc.categoryName ?? t("backend.ged.documents.no_category") }}</td>
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <AppBadge :color="DOCUMENT_STATUS_BADGE[doc.status]">{{ doc.statusLabel }}</AppBadge>
                            </td>
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <span v-if="doc.fileName" class="flex items-center gap-1 text-xs text-muted"><Paperclip class="w-3 h-3" :stroke-width="2" /> {{ doc.fileName }}</span>
                                <span v-else class="text-muted text-xs">—</span>
                            </td>
                            <td class="px-6 py-3 text-right hidden lg:table-cell text-xs text-muted tabular-nums">
                                <span v-if="doc.fileSize">{{ formatSize(doc.fileSize) }}</span>
                                <span v-else>—</span>
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
                                    <AppIconButton v-if="doc.fileUrl" color="default" :title="t('shared.common.qr_code')" v-on:click="openQr(doc)"><QrCode class="w-4 h-4" :stroke-width="2" /></AppIconButton>
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
                    :placeholder="t('backend.ged.documents.title_placeholder')"
                    :error="createErrors.title"
                    required
                />
                <AppInput v-model="newDoc.description" :label="t('backend.ged.documents.description')" :placeholder="t('backend.ged.documents.description_placeholder')" />
                <template v-if="newDoc.mimeType?.startsWith('image/')">
                    <AppInput v-model="newDoc.alt" :label="t('backend.ged.documents.alt')" :placeholder="t('backend.ged.documents.alt_placeholder')" />
                    <AppInput v-model="newDoc.caption" :label="t('backend.ged.documents.caption')" :placeholder="t('backend.ged.documents.caption_placeholder')" />
                </template>
                <AppMultiselect
                    v-model="newDoc.categoryId"
                    :label="t('backend.ged.documents.category')"
                    :options="categoryOptions"
                    :allow-empty="true"
                    :placeholder="t('backend.ged.documents.no_category')"
                />
                <AppMultiselect
                    v-if="tags.length"
                    v-model="newDoc.tagIds"
                    :label="t('backend.ged.documents.tags')"
                    :options="tagOptions"
                    :multiple="true"
                    :allow-empty="true"
                    :placeholder="t('backend.ged.documents.no_tags')"
                />
                <AppMultiselect
                    v-if="folders.length"
                    v-model="newDoc.folderId"
                    :label="t('backend.ged.documents.folder')"
                    :options="folderOptions"
                    :allow-empty="true"
                    :placeholder="t('backend.ged.documents.no_folder')"
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
                                <Upload class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.ged.documents.choose_file") }}
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
            max-width="4xl"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <div :class="editingDoc?.fileUrl ? 'grid grid-cols-1 md:grid-cols-2 gap-5 items-start' : 'space-y-4'">
                <!-- File preview (left column) -->
                <AppFilePreview
                    v-if="editingDoc?.fileUrl"
                    :url="editingDoc.fileUrl"
                    :mime="editingDoc.fileMime"
                    :name="editingDoc.fileName"
                    :alt="editingDoc.alt ?? editingDoc.title"
                    max-height="28rem"
                    class="md:sticky md:top-0"
                />

                <!-- Form fields (right column) -->
                <div class="space-y-4">
                    <AppInput
                        v-model="editForm.title"
                        :label="t('backend.ged.documents.title')"
                        :placeholder="t('backend.ged.documents.title_placeholder')"
                        :error="editErrors.title"
                        required
                    />
                    <AppInput v-model="editForm.description" :label="t('backend.ged.documents.description')" :placeholder="t('backend.ged.documents.description_placeholder')" />
                    <template v-if="editForm.mimeType?.startsWith('image/')">
                        <AppInput v-model="editForm.alt" :label="t('backend.ged.documents.alt')" :placeholder="t('backend.ged.documents.alt_placeholder')" />
                        <AppInput v-model="editForm.caption" :label="t('backend.ged.documents.caption')" :placeholder="t('backend.ged.documents.caption_placeholder')" />
                    </template>
                    <AppMultiselect
                        v-model="editForm.categoryId"
                        :label="t('backend.ged.documents.category')"
                        :options="categoryOptions"
                        :allow-empty="true"
                        :placeholder="t('backend.ged.documents.no_category')"
                    />
                    <AppMultiselect
                        v-if="tags.length"
                        v-model="editForm.tagIds"
                        :label="t('backend.ged.documents.tags')"
                        :options="tagOptions"
                        :multiple="true"
                        :allow-empty="true"
                        :placeholder="t('backend.ged.documents.no_tags')"
                    />
                    <AppMultiselect
                        v-if="folders.length"
                        v-model="editForm.folderId"
                        :label="t('backend.ged.documents.folder')"
                        :options="folderOptions"
                        :allow-empty="true"
                        :placeholder="t('backend.ged.documents.no_folder')"
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
                                    <Upload class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.ged.documents.choose_file") }}
                                </AppButton>
                            </template>
                        </AppFileInput>
                        <span v-if="editForm.fileName" class="text-sm text-muted flex items-center gap-1"><FileText class="w-4 h-4" :stroke-width="2" /> {{ editForm.fileName }}</span>
                    </div>
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
            <p class="text-sm text-primary">{{ t("backend.ged.documents.delete_confirm", { title: pendingDelete?.title ?? "" }) }}</p>
            <p class="text-sm text-secondary">{{ t("backend.ged.documents.delete_warning") }}</p>
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
            max-width="5xl"
            :closeable="false"
            v-on:close="viewingDoc = null"
        >
            <template v-if="viewingDoc">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
                    <!-- Document preview (left, prominent) -->
                    <div v-if="viewingDoc.fileUrl" class="lg:col-span-3 rounded-lg border border-line overflow-hidden">
                        <AppImagePreview v-if="viewingDoc.fileMime?.startsWith('image/')" :src="viewingDoc.fileUrl" :alt="viewingDoc.alt ?? viewingDoc.fileName" full />
                        <iframe
                            v-else-if="viewingDoc.fileMime === 'application/pdf'"
                            :src="viewingDoc.fileUrl"
                            class="w-full h-[36rem]"
                            :title="viewingDoc.fileName"
                        />
                        <div v-else class="flex flex-col items-center justify-center gap-3 px-4 py-16 bg-surface-2">
                            <FileText class="w-16 h-16 text-muted" :stroke-width="1.25" />
                            <p class="text-sm font-medium text-primary truncate max-w-full">{{ viewingDoc.fileName }}</p>
                            <p v-if="viewingDoc.fileMime" class="text-xs text-muted">{{ viewingDoc.fileMime }}</p>
                        </div>
                    </div>

                    <!-- Metadata (right) -->
                    <div :class="viewingDoc.fileUrl ? 'lg:col-span-2 space-y-4' : 'lg:col-span-5 space-y-4'">
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
                            <div v-if="viewingDoc.fileName">
                                <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("backend.ged.documents.file") }}</dt>
                                <dd class="text-secondary truncate" :title="viewingDoc.fileName">{{ viewingDoc.fileName }}</dd>
                            </div>
                            <div v-if="viewingDoc.fileSize">
                                <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("backend.ged.documents.size") }}</dt>
                                <dd class="text-secondary tabular-nums">{{ formatSize(viewingDoc.fileSize) }}</dd>
                            </div>
                            <div v-if="viewingDoc.width && viewingDoc.height">
                                <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("backend.ged.documents.dimensions") }}</dt>
                                <dd class="text-secondary tabular-nums">{{ viewingDoc.width }}×{{ viewingDoc.height }}</dd>
                            </div>
                            <div v-if="viewingDoc.fileMime">
                                <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("backend.ged.documents.type") }}</dt>
                                <dd class="text-secondary">{{ viewingDoc.fileMime }}</dd>
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

                        <!-- Image metadata (alt / caption) -->
                        <dl v-if="viewingDoc.fileMime?.startsWith('image/') && (viewingDoc.alt || viewingDoc.caption)" class="space-y-3 text-sm">
                            <div v-if="viewingDoc.alt">
                                <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("backend.ged.documents.alt") }}</dt>
                                <dd class="text-primary">{{ viewingDoc.alt }}</dd>
                            </div>
                            <div v-if="viewingDoc.caption">
                                <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("backend.ged.documents.caption") }}</dt>
                                <dd class="text-primary">{{ viewingDoc.caption }}</dd>
                            </div>
                        </dl>

                        <!-- Permalink -->
                        <div v-if="viewingDoc.fileUrl">
                            <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t("backend.ged.documents.permalink") }}</dt>
                            <div class="flex items-center gap-2">
                                <code class="text-xs text-secondary bg-surface-2 rounded px-2 py-1 truncate flex-1">{{ permalinkFor(viewingDoc) }}</code>
                                <AppIconButton color="default" :title="t('shared.common.copy')" v-on:click="copy(permalinkFor(viewingDoc))">
                                    <Copy class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div v-if="viewingDoc.tags?.length" class="flex flex-wrap gap-1.5">
                            <DocumentTagChip v-for="tag in viewingDoc.tags" :key="tag.id" :tag="tag" />
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
                    </div>
                </div>
            </template>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="viewingDoc = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.close") }}</AppButton>
                    <AppButton
                        v-if="viewingDoc?.fileUrl"
                        variant="ghost"
                        size="md"
                        v-on:click="openQr(viewingDoc)"
                    >
                        <QrCode class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.qr_code") }}
                    </AppButton>
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

        <AppQrCodeModal :item="qrDoc" v-on:close="closeQr" />
    </div>
</template>
