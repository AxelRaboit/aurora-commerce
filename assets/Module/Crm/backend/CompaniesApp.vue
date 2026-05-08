<script setup>
import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppLink from "@/shared/components/nav/AppLink.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import { Plus, Pencil, Trash2, Eye, Save, X } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required, url } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();
const props = defineProps({
    companies: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
    showPath: { type: String, default: "" },
});

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.companies },
);

function emptyForm() {
    return { name: "", industry: "", website: "", phone: "", address: "", notes: "" };
}

// Create
const showCreate = ref(false);
const newCompany = ref(emptyForm());
const { errors: createErrors, validate: validateCreate, clearErrors: clearCreate, setErrors: setCreateErrors } = useForm();
const { loading: createLoading, request: createRequest } = useApiRequest();
function openCreate() { newCompany.value = emptyForm(); clearCreate(); showCreate.value = true; }
async function submitCreate() {
    if (!validateCreate({
        name: () => required(t("backend.crm.companies.errors.name_required"))(newCompany.value.name),
        website: () => url(t("backend.crm.companies.errors.website_invalid"))(newCompany.value.website),
    })) return;
    const data = await createRequest(props.createPath, newCompany.value);
    if (!data) return;
    if (data.success) { showCreate.value = false; toast.success(t('backend.crm.companies.created')); reset(); }
    else setCreateErrors(translateServerErrors(t, data.errors));
}

// Edit
const showEdit = ref(false);
const editingCompany = ref(null);
const editForm = ref(emptyForm());
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();
function openEdit(company) {
    editingCompany.value = company;
    editForm.value = { name: company.name, industry: company.industry ?? "", website: company.website ?? "", phone: company.phone ?? "", address: company.address ?? "", notes: company.notes ?? "" };
    clearEdit(); showEdit.value = true;
}
async function submitEdit() {
    if (!validateEdit({
        name: () => required(t("backend.crm.companies.errors.name_required"))(editForm.value.name),
        website: () => url(t("backend.crm.companies.errors.website_invalid"))(editForm.value.website),
    })) return;
    const updateUrl = buildPath(props.updatePath, { id: editingCompany.value.id });
    const data = await editRequest(updateUrl, editForm.value);
    if (!data) return;
    if (data.success) { showEdit.value = false; toast.success(t('backend.crm.companies.updated')); reset(); }
    else setEditErrors(translateServerErrors(t, data.errors));
}

// --- Delete ---
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reset(), 'admin.crm.companies.deleted',
);
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('backend.crm.companies.searchPlaceholder')"
                v-on:search="onSearch"
            />
            <AppButton
                v-if="can('crm.companies.manage')"
                variant="primary"
                size="md"
                class="w-full sm:w-auto"
                v-on:click="openCreate"
            >
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('backend.crm.companies.add') }}
            </AppButton>
        </div>
        <div class="sm:hidden space-y-3">
            <div v-for="company in items" :key="company.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-primary">{{ company.name }}</p>
                        <p v-if="company.industry" class="text-xs text-muted mt-0.5">{{ company.industry }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <AppLink v-if="company.website" :href="company.website" target="_blank" class="text-xs text-accent-400 truncate hover:underline">{{ company.website }}</AppLink>
                    <span v-else class="text-xs text-muted">—</span>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton color="sky" :href="buildPath(showPath, { id: company.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('crm.companies.manage')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(company)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('crm.companies.manage')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(company)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.companies.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.companies.industry') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.crm.companies.website') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="company in items" :key="company.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3">
                            <span class="font-medium text-primary">{{ company.name }}</span>
                        </td>
                        <td class="px-6 py-3 text-secondary">{{ company.industry ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">
                            <AppLink
                                v-if="company.website"
                                :href="company.website"
                                target="_blank"
                                rel="noopener"
                                class="text-accent-400 hover:underline truncate max-w-xs block"
                            >
                                {{ company.website }}
                            </AppLink>
                            <span v-else>—</span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="sky" :href="buildPath(showPath, { id: company.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('crm.companies.manage')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(company)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('crm.companies.manage')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(company)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!items?.length">
                        <td :colspan="4" class="px-6 py-8 text-center text-sm text-muted">{{ t('backend.crm.companies.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <AppModal :show="showCreate" :title="t('backend.crm.companies.create')" v-on:close="showCreate = false">
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="newCompany.name"
                    :label="t('backend.crm.companies.name')"
                    :placeholder="t('backend.crm.companies.namePlaceholder')"
                    :error="createErrors.name"
                    required
                />
                <AppInput v-model="newCompany.industry" :label="t('backend.crm.companies.industry')" :placeholder="t('backend.crm.companies.industryPlaceholder')" />
                <AppInput v-model="newCompany.website" :label="t('backend.crm.companies.website')" :placeholder="t('backend.crm.companies.websitePlaceholder')" :error="createErrors.website" />
                <AppInput v-model="newCompany.phone" :label="t('backend.crm.companies.phone')" :placeholder="t('backend.crm.companies.phonePlaceholder')" />
                <AppInput v-model="newCompany.address" :label="t('backend.crm.companies.address')" :placeholder="t('backend.crm.companies.addressPlaceholder')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="showEdit" :title="t('backend.crm.companies.edit', { name: editingCompany?.name ?? '' })" v-on:close="showEdit = false">
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('backend.crm.companies.name')"
                    :placeholder="t('backend.crm.companies.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <AppInput v-model="editForm.industry" :label="t('backend.crm.companies.industry')" :placeholder="t('backend.crm.companies.industryPlaceholder')" />
                <AppInput v-model="editForm.website" :label="t('backend.crm.companies.website')" :placeholder="t('backend.crm.companies.websitePlaceholder')" :error="editErrors.website" />
                <AppInput v-model="editForm.phone" :label="t('backend.crm.companies.phone')" :placeholder="t('backend.crm.companies.phonePlaceholder')" />
                <AppInput v-model="editForm.address" :label="t('backend.crm.companies.address')" :placeholder="t('backend.crm.companies.addressPlaceholder')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t('backend.crm.companies.deleteConfirm', { name: pendingDelete?.name ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.crm.companies.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="confirmDelete(null)"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
