<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useDocumentsForm, DOCUMENT_STATUS_BADGE } from "./composables/useDocumentsForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppColorPicker from "@/shared/components/form/AppColorPicker.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import MediaPickerModal from "@core/backend/media/MediaPickerModal.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useFileSize } from "@/shared/composables/format/useFileSize.js";
import { Plus, Eye, Pencil, Trash2, Save, FileText, Paperclip, X, Folder, Download } from "lucide-vue-next";

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
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
    mediaPickerPath: { type: String, default: "" },
});

const categoryOptions = props.categories.map((c) => ({ value: c.id, label: c.name }));
const tagOptions = props.tags.map((tag) => ({ value: tag.id, label: tag.name }));
const folderOptions = props.folders.map((f) => ({ value: f.id, label: f.name }));

// ── Detail modal ─────────────────────────────────────────────────────────────
const viewingDoc = ref(null);

function viewDoc(doc) {
    viewingDoc.value = doc;
}

function openEditFromDetail() {
    const doc = viewingDoc.value;
    viewingDoc.value = null;
    openEdit(doc);
}

// ── Filters ──────────────────────────────────────────────────────────────────
const filterCategoryId = ref(null);
const filterTagId = ref(null);
const filterFolderId = ref(null);
const filterStatus = ref(null);

function applyFilter() {
    reset();
}

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.documents,
        extraParams: () => ({
            categoryId: filterCategoryId.value || undefined,
            tagId: filterTagId.value || undefined,
            folderId: filterFolderId.value || undefined,
            status: filterStatus.value || undefined,
        }),
    },
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
        <!-- Search + add -->
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput v-model="searchInput" :placeholder="t('backend.ged.documents.searchPlaceholder')" v-on:search="onSearch" />
            <AppButton
                v-if="can('ged.documents.create')"
                variant="primary"
                size="md"
                class="w-full sm:w-auto"
                v-on:click="openCreate"
            >
                <Plus class="w-4 h-4" :stroke-width="2" /> {{ t("backend.ged.documents.add") }}
            </AppButton>
        </div>

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
            <button
                v-if="filterCategoryId || filterTagId || filterFolderId || filterStatus"
                type="button"
                class="text-xs text-muted hover:text-primary transition flex items-center gap-1"
                v-on:click="filterCategoryId = null; filterTagId = null; filterFolderId = null; filterStatus = null; applyFilter()"
            >
                <X class="w-3 h-3" :stroke-width="2" /> {{ t("shared.common.reset") }}
            </button>
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
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="default" :title="t('shared.common.view')" v-on:click="viewDoc(doc)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ged.documents.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(doc)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ged.documents.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(doc)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
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
                <div class="flex items-center gap-3">
                    <AppButton variant="ghost" size="sm" type="button" v-on:click="showMediaPickerCreate = true">
                        <Paperclip class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.ged.documents.chooseFile") }}
                    </AppButton>
                    <span v-if="newDoc.fileName" class="text-sm text-muted flex items-center gap-1"><FileText class="w-4 h-4" :stroke-width="2" /> {{ newDoc.fileName }}</span>
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
                <div class="flex items-center gap-3">
                    <AppButton variant="ghost" size="sm" type="button" v-on:click="showMediaPickerEdit = true">
                        <Paperclip class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.ged.documents.chooseFile") }}
                    </AppButton>
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

                    <!-- File -->
                    <div v-if="viewingDoc.fileUrl" class="rounded-lg border border-line overflow-hidden">
                        <img
                            v-if="viewingDoc.fileMime?.startsWith('image/')"
                            :src="viewingDoc.fileUrl"
                            :alt="viewingDoc.fileName"
                            class="w-full max-h-64 object-contain bg-surface-2"
                        >
                        <iframe
                            v-else-if="viewingDoc.fileMime === 'application/pdf'"
                            :src="viewingDoc.fileUrl"
                            class="w-full h-64"
                            :title="viewingDoc.fileName"
                        />
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
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Media pickers -->
        <MediaPickerModal :show="showMediaPickerCreate" :list-path="mediaPickerPath" v-on:close="showMediaPickerCreate = false" v-on:select="onFilePickedCreate" />
        <MediaPickerModal :show="showMediaPickerEdit" :list-path="mediaPickerPath" v-on:close="showMediaPickerEdit = false" v-on:select="onFilePickedEdit" />
    </div>
</template>
