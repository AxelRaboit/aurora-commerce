<script setup>
import { ref, computed } from "vue";
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
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppDatePicker from "@/shared/components/form/AppDatePicker.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import { List, Columns2, Pencil, Trash2, Eye, Plus, Save, } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required } from "@/shared/utils/validation/validators.js";

const { t } = useI18n();
const props = defineProps({
    deals: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    stages: { type: Array, default: () => [] },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
    contactsListPath: { type: String, required: true },
    companiesListPath: { type: String, required: true },
    kanbanPath: { type: String, default: "" },
    showPath: { type: String, default: "" },
});

const stageOptions = computed(() =>
    props.stages.map(s => ({ value: s, label: t(`admin.crm.deals.stages.${s}`) }))
);

const stageBadge = (stage) => ({
    lead: "bg-slate-500/15 text-slate-400",
    qualified: "bg-blue-500/15 text-blue-400",
    proposal: "bg-violet-500/15 text-violet-400",
    negotiation: "bg-amber-500/15 text-amber-400",
    won: "bg-emerald-500/15 text-emerald-400",
    lost: "bg-red-500/15 text-red-400",
}[stage] ?? "bg-slate-500/15 text-slate-400");

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.deals },
);

function emptyForm() {
    return { name: "", stage: "lead", value: "", contactId: "", companyId: "", closingDate: "", notes: "" };
}

// Create
const showCreate = ref(false);
const newDeal = ref(emptyForm());
const { errors: createErrors, validate: validateCreate, clearErrors: clearCreate, setErrors: setCreateErrors } = useForm();
const { loading: createLoading, request: createRequest } = useApiRequest();
function openCreate() { newDeal.value = emptyForm(); clearCreate(); showCreate.value = true; }
function goToKanban() { location.href = props.kanbanPath; }
async function submitCreate() {
    if (!validateCreate({ name: () => required(t("admin.crm.deals.errors.name_required"))(newDeal.value.name) })) return;
    const data = await createRequest(props.createPath, newDeal.value);
    if (!data) return;
    if (data.success) { showCreate.value = false; toast.success(t('admin.crm.deals.created')); reset(); }
    else setCreateErrors(data.errors ?? {});
}

// Edit
const showEdit = ref(false);
const editingDeal = ref(null);
const editForm = ref(emptyForm());
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();
function openEdit(deal) {
    editingDeal.value = deal;
    editForm.value = { name: deal.name, stage: deal.stage, value: deal.value ?? "", contactId: deal.contact?.id ?? "", companyId: deal.company?.id ?? "", closingDate: deal.closingDate ?? "", notes: deal.notes ?? "" };
    clearEdit(); showEdit.value = true;
}
async function submitEdit() {
    if (!validateEdit({ name: () => required(t("admin.crm.deals.errors.name_required"))(editForm.value.name) })) return;
    const url = props.updatePath.replace("__id__", editingDeal.value.id);
    const data = await editRequest(url, editForm.value);
    if (!data) return;
    if (data.success) { showEdit.value = false; toast.success(t('admin.crm.deals.updated')); reset(); }
    else setEditErrors(data.errors ?? {});
}

// --- Delete ---
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reset(), 'admin.crm.deals.deleted',
);
</script>

<template>
    <div class="space-y-4">
        <div v-if="kanbanPath" class="flex border-b border-line/40 text-sm">
            <button type="button" class="px-4 py-2 border-b-2 border-accent-500 text-primary font-medium transition-colors flex items-center gap-1.5">
                <List class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.crm.deals.listView') }}
            </button>
            <button type="button" class="px-4 py-2 border-b-2 border-transparent text-muted hover:text-secondary font-medium transition-colors flex items-center gap-1.5" v-on:click="goToKanban">
                <Columns2 class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.crm.deals.kanbanView') }}
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('admin.crm.deals.searchPlaceholder')"
                v-on:search="onSearch"
            />
            <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.crm.deals.add') }}
            </AppButton>
        </div>
        <div class="sm:hidden space-y-3">
            <div v-for="deal in items" :key="deal.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-primary">{{ deal.name }}</p>
                        <p v-if="deal.contact || deal.company" class="text-xs text-muted mt-0.5">{{ deal.contact?.fullName ?? deal.company?.name }}</p>
                    </div>
                    <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium shrink-0', stageBadge(deal.stage)]">
                        {{ t(`admin.crm.deals.stages.${deal.stage}`) }}
                    </span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <span class="text-xs text-secondary font-medium">{{ deal.value ? `${Number(deal.value).toLocaleString()} €` : '—' }}</span>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton v-if="showPath" color="sky" :href="showPath.replace('__id__', deal.id)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(deal)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(deal)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.crm.deals.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.crm.deals.stage') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.crm.deals.contact') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('admin.crm.deals.value') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="deal in items" :key="deal.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-6 py-3 font-medium text-primary">{{ deal.name }}</td>
                        <td class="px-6 py-3">
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', stageBadge(deal.stage)]">
                                {{ t(`admin.crm.deals.stages.${deal.stage}`) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ deal.contact?.fullName ?? deal.company?.name ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">{{ deal.value ? `${Number(deal.value).toLocaleString()} €` : '—' }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="showPath" color="sky" :href="showPath.replace('__id__', deal.id)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(deal)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(deal)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!items?.length">
                        <td :colspan="5" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.crm.deals.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <AppModal :show="showCreate" v-on:close="showCreate = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.crm.deals.create') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="newDeal.name"
                    :label="t('admin.crm.deals.name')"
                    :placeholder="t('admin.crm.deals.namePlaceholder')"
                    :error="createErrors.name"
                    required
                />
                <AppSelect v-model="newDeal.stage" :label="t('admin.crm.deals.stage')">
                    <option v-for="opt in stageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <AppInput v-model="newDeal.value" :label="t('admin.crm.deals.value')" :placeholder="t('admin.crm.deals.valuePlaceholder')" />
                <AppDatePicker v-model="newDeal.closingDate" :label="t('admin.crm.deals.closingDate')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="showEdit" v-on:close="showEdit = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.crm.deals.edit', { name: editingDeal?.name ?? '' }) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('admin.crm.deals.name')"
                    :placeholder="t('admin.crm.deals.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <AppSelect v-model="editForm.stage" :label="t('admin.crm.deals.stage')">
                    <option v-for="opt in stageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </AppSelect>
                <AppInput v-model="editForm.value" :label="t('admin.crm.deals.value')" :placeholder="t('admin.crm.deals.valuePlaceholder')" />
                <AppDatePicker v-model="editForm.closingDate" :label="t('admin.crm.deals.closingDate')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t('admin.crm.deals.deleteConfirm', { name: pendingDelete?.name ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.crm.deals.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="confirmDelete(null)">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
