<script setup>
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { ref } from "vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import { Plus, Pencil, Trash2, Save } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();
const props = defineProps({
    categories: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
});

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath, { initialSearch: props.search, initialData: props.categories },
);

function emptyForm() { return { name: "", description: "" }; }

const showCreate = ref(false);
const newCategory = ref(emptyForm());
const { errors: createErrors, validate: validateCreate, clearErrors: clearCreate, setErrors: setCreateErrors } = useForm();
const { loading: createLoading, request: createRequest } = useApiRequest();
function openCreate() { newCategory.value = emptyForm(); clearCreate(); showCreate.value = true; }
async function submitCreate() {
    if (!validateCreate({ name: () => required(t("backend.ged.categories.errors.name_required"))(newCategory.value.name) })) return;
    const data = await createRequest(props.createPath, newCategory.value);
    if (!data) return;
    if (data.success) { showCreate.value = false; toast.success(t("backend.ged.categories.created")); reset(); }
    else setCreateErrors(translateServerErrors(t, data.errors));
}

const showEdit = ref(false);
const editingCategory = ref(null);
const editForm = ref(emptyForm());
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();
function openEdit(category) {
    editingCategory.value = category;
    editForm.value = { name: category.name, description: category.description ?? "" };
    clearEdit(); showEdit.value = true;
}
async function submitEdit() {
    if (!validateEdit({ name: () => required(t("backend.ged.categories.errors.name_required"))(editForm.value.name) })) return;
    const url = buildPath(props.updatePath, { id: editingCategory.value.id });
    const data = await editRequest(url, editForm.value);
    if (!data) return;
    if (data.success) { showEdit.value = false; toast.success(t("backend.ged.categories.updated")); reset(); }
    else setEditErrors(translateServerErrors(t, data.errors));
}

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reset(), "admin.ged.categories.deleted",
);
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput v-model="searchInput" :placeholder="t('backend.ged.categories.searchPlaceholder')" v-on:search="onSearch" />
            <AppButton
                v-if="can('ged.documents.manage')"
                variant="primary"
                size="md"
                class="w-full sm:w-auto"
                v-on:click="openCreate"
            >
                <Plus class="w-4 h-4" :stroke-width="2" /> {{ t("backend.ged.categories.add") }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.ged.categories.name") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.ged.categories.slug") }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="cat in items" :key="cat.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3 font-medium text-primary">{{ cat.name }}</td>
                        <td class="px-6 py-3 text-muted font-mono text-xs hidden md:table-cell">{{ cat.slug }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="can('ged.documents.manage')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(cat)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ged.documents.manage')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(cat)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td :colspan="3" class="px-6 py-8 text-center text-sm text-muted">{{ t("backend.ged.categories.empty") }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <AppModal :show="showCreate" v-on:close="showCreate = false">
            <h3 class="text-lg font-semibold text-primary">{{ t("backend.ged.categories.create") }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="newCategory.name"
                    :label="t('backend.ged.categories.name')"
                    :placeholder="t('backend.ged.categories.namePlaceholder')"
                    :error="createErrors.name"
                    required
                />
                <AppInput v-model="newCategory.description" :label="t('backend.ged.categories.description')" :placeholder="t('backend.ged.categories.descriptionPlaceholder')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="showEdit" v-on:close="showEdit = false">
            <h3 class="text-lg font-semibold text-primary">{{ t("backend.ged.categories.edit", { name: editingCategory?.name ?? "" }) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('backend.ged.categories.name')"
                    :placeholder="t('backend.ged.categories.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <AppInput v-model="editForm.description" :label="t('backend.ged.categories.description')" :placeholder="t('backend.ged.categories.descriptionPlaceholder')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="confirmDelete(null)">
            <p class="text-sm text-primary">{{ t("backend.ged.categories.deleteConfirm", { name: pendingDelete?.name ?? "" }) }}</p>
            <p class="text-sm text-secondary">{{ t("backend.ged.categories.deleteWarning") }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="confirmDelete(null)">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t("shared.common.delete") }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
