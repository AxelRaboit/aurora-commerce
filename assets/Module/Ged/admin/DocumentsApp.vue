<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import MediaPickerModal from "@core/admin/media/MediaPickerModal.vue";
import { Plus, Pencil, Trash2, Save, FileText, Paperclip } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

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

const statusOptions = [
    { value: "draft", label: t("backend.ged.documents.status_draft") },
    { value: "published", label: t("backend.ged.documents.status_published") },
    { value: "archived", label: t("backend.ged.documents.status_archived") },
];

const statusBadgeColor = { draft: "secondary", published: "success", archived: "accent" };

const categoryOptions = props.categories.map((c) => ({ value: c.id, label: c.name }));

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath, { initialSearch: props.search, initialData: props.documents },
);

function emptyForm() { return { title: "", description: "", status: "draft", categoryId: null, fileId: null, fileName: null }; }

// Create
const showCreate = ref(false);
const newDoc = ref(emptyForm());
const showMediaPickerCreate = ref(false);
const { errors: createErrors, validate: validateCreate, clearErrors: clearCreate, setErrors: setCreateErrors } = useForm();
const { loading: createLoading, request: createRequest } = useApiRequest();
function openCreate() { newDoc.value = emptyForm(); clearCreate(); showCreate.value = true; }
function onFilePickedCreate(media) { newDoc.value.fileId = media.id; newDoc.value.fileName = media.fileName; showMediaPickerCreate.value = false; }
async function submitCreate() {
    if (!validateCreate({ title: () => required(t("backend.ged.documents.errors.title_required"))(newDoc.value.title) })) return;
    const payload = { ...newDoc.value };
    const data = await createRequest(props.createPath, payload);
    if (!data) return;
    if (data.success) { showCreate.value = false; toast.success(t("backend.ged.documents.created")); reset(); }
    else setCreateErrors(translateServerErrors(t, data.errors));
}

// Edit
const showEdit = ref(false);
const editingDoc = ref(null);
const editForm = ref(emptyForm());
const showMediaPickerEdit = ref(false);
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();
function openEdit(doc) {
    editingDoc.value = doc;
    editForm.value = { title: doc.title, description: doc.description ?? "", status: doc.status, categoryId: doc.categoryId ?? null, fileId: doc.fileId, fileName: doc.fileName };
    clearEdit(); showEdit.value = true;
}
function onFilePickedEdit(media) { editForm.value.fileId = media.id; editForm.value.fileName = media.fileName; showMediaPickerEdit.value = false; }
async function submitEdit() {
    if (!validateEdit({ title: () => required(t("backend.ged.documents.errors.title_required"))(editForm.value.title) })) return;
    const url = buildPath(props.updatePath, { id: editingDoc.value.id });
    const payload = { ...editForm.value };
    const data = await editRequest(url, payload);
    if (!data) return;
    if (data.success) { showEdit.value = false; toast.success(t("backend.ged.documents.updated")); reset(); }
    else setEditErrors(translateServerErrors(t, data.errors));
}

// Delete
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reset(), "admin.ged.documents.deleted",
);
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
                            <AppBadge :color="statusBadgeColor[doc.status]">{{ doc.statusLabel }}</AppBadge>
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
                        <td :colspan="5" class="px-6 py-8 text-center text-sm text-muted">{{ t("backend.ged.documents.empty") }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <!-- Create modal -->
        <AppModal :show="showCreate" v-on:close="showCreate = false">
            <h3 class="text-lg font-semibold text-primary">{{ t("backend.ged.documents.create") }}</h3>
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
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <!-- Edit modal -->
        <AppModal :show="showEdit" v-on:close="showEdit = false">
            <h3 class="text-lg font-semibold text-primary">{{ t("backend.ged.documents.edit", { title: editingDoc?.title ?? "" }) }}</h3>
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
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <!-- Delete modal -->
        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="confirmDelete(null)">
            <p class="text-sm text-primary">{{ t("backend.ged.documents.deleteConfirm", { title: pendingDelete?.title ?? "" }) }}</p>
            <p class="text-sm text-secondary">{{ t("backend.ged.documents.deleteWarning") }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="confirmDelete(null)">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t("shared.common.delete") }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <!-- Media pickers -->
        <MediaPickerModal :show="showMediaPickerCreate" :list-path="mediaPickerPath" v-on:close="showMediaPickerCreate = false" v-on:pick="onFilePickedCreate" />
        <MediaPickerModal :show="showMediaPickerEdit" :list-path="mediaPickerPath" v-on:close="showMediaPickerEdit = false" v-on:pick="onFilePickedEdit" />
    </div>
</template>
