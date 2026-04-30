<script setup>
import { ref, computed } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useUrlSyncedState } from "@/shared/composables/list/useUrlSyncedState.js";
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
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import { List, Columns2, Pencil, Trash2, Eye, Plus, Save } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required } from "@/shared/utils/validation/validators.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { stageBadge, stageBadgeBordered } from "@crm/utils/deals/stageStyles.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

const { t } = useI18n();

const props = defineProps({
    initialView: { type: String, default: "list" }, // "list" | "kanban"
    kanbanColumns: { type: Object, default: null },
    deals: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    stages: { type: Array, default: () => [] },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
    kanbanColumnsPath: { type: String, default: "" },
    listRoutePath: { type: String, default: "" },
    kanbanRoutePath: { type: String, default: "" },
    updateStagePath: { type: String, default: "" },
    contactsListPath: { type: String, required: true },
    companiesListPath: { type: String, required: true },
    showPath: { type: String, default: "" },
});

// ── View switching ───────────────────────────────────────────────────────────
const { state: view, set: setView } = useUrlSyncedState({
    initial: props.initialView === "kanban" ? "kanban" : "list",
    serialize: (next) => {
        const path = next === "kanban" ? props.kanbanRoutePath : props.listRoutePath;
        if (!path) return null;
        // Preserve current query string (?search=…&page=…) when swapping views.
        const target = new URL(path, window.location.origin);
        for (const [key, value] of new URLSearchParams(window.location.search)) {
            target.searchParams.set(key, value);
        }
        return target;
    },
    deserialize: (event) => event.state?.value
        ?? (window.location.pathname.endsWith("/kanban") ? "kanban" : "list"),
    onSync: (next) => {
        if (next === "kanban") ensureKanbanColumns();
    },
});

// ── List view ────────────────────────────────────────────────────────────────
const stageOptions = computed(() =>
    props.stages.map(s => ({ value: s, label: t(`admin.crm.deals.stages.${s}`) }))
);


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
async function submitCreate() {
    if (!validateCreate({ name: () => required(t("admin.crm.deals.errors.name_required"))(newDeal.value.name) })) return;
    const data = await createRequest(props.createPath, newDeal.value);
    if (!data) return;
    if (data.success) {
        showCreate.value = false;
        toast.success(t('admin.crm.deals.created'));
        reset();
        if (kanbanColumnsLoaded.value) await ensureKanbanColumns(true); // refresh kanban too
    } else {
        setCreateErrors(translateServerErrors(t, data.errors));
    }
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
    const url = buildPath(props.updatePath, { id: editingDeal.value.id });
    const data = await editRequest(url, editForm.value);
    if (!data) return;
    if (data.success) {
        showEdit.value = false;
        toast.success(t('admin.crm.deals.updated'));
        reset();
        if (kanbanColumnsLoaded.value) await ensureKanbanColumns(true);
    } else {
        setEditErrors(translateServerErrors(t, data.errors));
    }
}

// Delete
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath,
    () => {
        reset();
        if (kanbanColumnsLoaded.value) ensureKanbanColumns(true);
    },
    'admin.crm.deals.deleted',
);

// ── Kanban view ──────────────────────────────────────────────────────────────
const localColumns = ref(
    props.kanbanColumns
        ? Object.fromEntries(props.stages.map(s => [s, [...(props.kanbanColumns[s] ?? [])]]))
        : Object.fromEntries(props.stages.map(s => [s, []])),
);
const kanbanColumnsLoaded = ref(props.kanbanColumns !== null);
const kanbanLoading = ref(false);
const activeStage = ref(props.stages[0] ?? 'lead');

async function ensureKanbanColumns(force = false) {
    if (!force && kanbanColumnsLoaded.value) return;
    if (!props.kanbanColumnsPath) return;
    kanbanLoading.value = true;
    try {
        const response = await fetch(props.kanbanColumnsPath, { headers: { Accept: "application/json" } });
        if (!response.ok) throw new Error();
        const data = await response.json();
        const columns = data.columns ?? {};
        localColumns.value = Object.fromEntries(props.stages.map(s => [s, [...(columns[s] ?? [])]]));
        kanbanColumnsLoaded.value = true;
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        kanbanLoading.value = false;
    }
}

const totalByStage = computed(() =>
    Object.fromEntries(props.stages.map(s => [s, localColumns.value[s]?.length ?? 0]))
);

/**
 * Send a stage change to the backend without going through useApiRequest's
 * single-flight loading guard — multiple concurrent drops on the kanban must
 * not cancel each other.
 */
async function patchStage(dealId, stage) {
    try {
        const url = buildPath(props.updateStagePath, { id: dealId });
        const response = await fetch(url, {
            method: HttpMethod.Patch,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ stage }),
        });
        if (!response.ok) return null;
        return await response.json();
    } catch {
        return null;
    }
}

async function updateStageForDeal(deal, newStage) {
    if (deal.stage === newStage) return;
    const data = await patchStage(deal.id, newStage);
    if (data?.success) {
        localColumns.value[deal.stage] = localColumns.value[deal.stage].filter(d => d.id !== deal.id);
        deal.stage = newStage;
        localColumns.value[newStage] = [...(localColumns.value[newStage] ?? []), deal];
        activeStage.value = newStage;
    } else {
        toast.error(t("shared.common.error"));
    }
}

async function onDrop(event, targetStage) {
    // VueDraggable has already moved the item into localColumns[targetStage] via v-model.
    // Pick the deal up from its new location using newIndex, which is guaranteed defined
    // for `add` events. Falling back to __draggable_context broke after a vue-draggable-plus
    // upgrade — newIndex is the supported public API.
    const newIndex = event.newIndex;
    const deal = newIndex !== undefined ? localColumns.value[targetStage]?.[newIndex] : null;
    if (!deal || deal.stage === targetStage) return;

    const previousStage = deal.stage;
    const data = await patchStage(deal.id, targetStage);
    if (data?.success) {
        deal.stage = targetStage;
    } else {
        // Revert: move the card back to its previous column.
        localColumns.value[targetStage] = localColumns.value[targetStage].filter(d => d.id !== deal.id);
        localColumns.value[previousStage] = [...(localColumns.value[previousStage] ?? []), deal];
        toast.error(t("shared.common.error"));
    }
}
</script>

<template>
    <div class="flex flex-col md:flex-row gap-6">
        <nav v-if="kanbanRoutePath" class="hidden md:flex flex-col w-44 shrink-0 gap-0.5">
            <AppTab :active="view === 'list'" v-on:click="setView('list')">
                <List class="w-4 h-4 shrink-0" :stroke-width="2" />
                {{ t('admin.crm.deals.listView') }}
            </AppTab>
            <AppTab :active="view === 'kanban'" v-on:click="setView('kanban')">
                <Columns2 class="w-4 h-4 shrink-0" :stroke-width="2" />
                {{ t('admin.crm.deals.kanbanView') }}
            </AppTab>
        </nav>

        <div v-if="kanbanRoutePath" class="flex md:hidden gap-1 flex-wrap w-full">
            <AppTab :active="view === 'list'" size="sm" v-on:click="setView('list')">
                <List class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.crm.deals.listView') }}
            </AppTab>
            <AppTab :active="view === 'kanban'" size="sm" v-on:click="setView('kanban')">
                <Columns2 class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.crm.deals.kanbanView') }}
            </AppTab>
        </div>

        <div class="flex-1 min-w-0 space-y-4">
            <!-- LIST VIEW -->
            <div v-show="view === 'list'" class="space-y-4">
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
                                <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: deal.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
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
                                        <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: deal.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
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
            </div>

            <!-- KANBAN VIEW -->
            <div v-show="view === 'kanban'">
                <div v-if="kanbanLoading && !kanbanColumnsLoaded" class="py-12 text-center text-sm text-muted">
                    {{ t('shared.common.loading') }}
                </div>

                <div v-else>
                    <div class="sm:hidden">
                        <div class="flex overflow-x-auto gap-1.5 pb-2 scrollbar-thin">
                            <AppTab
                                v-for="stage in stages"
                                :key="stage"
                                size="sm"
                                shape-class="shrink-0 rounded-full border text-xs font-semibold"
                                :active="activeStage === stage"
                                :active-class="stageBadgeBordered(stage)"
                                inactive-class="bg-surface-2 text-muted border-transparent hover:text-primary"
                                v-on:click="activeStage = stage"
                            >
                                {{ t(`admin.crm.deals.stages.${stage}`) }}
                                <span :class="['inline-flex items-center justify-center min-w-4 h-4 rounded-full px-1 text-xs', activeStage === stage ? 'bg-white/20' : 'bg-surface-3 text-muted']">
                                    {{ localColumns[stage]?.length ?? 0 }}
                                </span>
                            </AppTab>
                        </div>

                        <div class="mt-3 space-y-2">
                            <p v-if="!localColumns[activeStage]?.length" class="py-8 text-center text-sm text-muted">
                                {{ t('admin.crm.deals.empty') }}
                            </p>
                            <div
                                v-for="deal in localColumns[activeStage]"
                                :key="deal.id"
                                class="bg-surface border border-line rounded-lg p-4 space-y-2"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-primary">{{ deal.name }}</p>
                                    <AppSelect
                                        :model-value="deal.stage"
                                        class="shrink-0"
                                        v-on:update:model-value="updateStageForDeal(deal, $event)"
                                    >
                                        <option v-for="s in stages" :key="s" :value="s">{{ t(`admin.crm.deals.stages.${s}`) }}</option>
                                    </AppSelect>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span v-if="deal.contact || deal.company" class="text-xs text-muted truncate">
                                        {{ deal.contact?.fullName ?? deal.company?.name }}
                                    </span>
                                    <span v-if="deal.value" class="text-xs font-semibold text-secondary ml-auto shrink-0">
                                        {{ Number(deal.value).toLocaleString() }} €
                                    </span>
                                </div>
                                <p v-if="deal.closingDate" class="text-xs text-muted">{{ deal.closingDate }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="hidden sm:flex gap-4 overflow-x-auto pb-4 min-h-[calc(100vh-16rem)]">
                        <div
                            v-for="stage in stages"
                            :key="stage"
                            class="shrink-0 w-72 flex flex-col gap-2"
                        >
                            <div :class="['flex items-center justify-between px-3 py-2 rounded-lg border text-xs font-semibold uppercase tracking-wide', stageBadgeBordered(stage)]">
                                <span>{{ t(`admin.crm.deals.stages.${stage}`) }}</span>
                                <span class="text-xs opacity-70">{{ totalByStage[stage] }}</span>
                            </div>
                            <VueDraggable
                                v-model="localColumns[stage]"
                                :group="{ name: 'deals', put: true, pull: true }"
                                :animation="150"
                                class="flex flex-col gap-2 min-h-12 flex-1"
                                v-on:add="(e) => onDrop(e, stage)"
                            >
                                <div
                                    v-for="deal in localColumns[stage]"
                                    :key="deal.id"
                                    class="bg-surface border border-line rounded-lg p-3 cursor-grab active:cursor-grabbing hover:border-accent-500/40 transition-colors select-none"
                                >
                                    <p class="text-sm font-medium text-primary leading-snug mb-1">{{ deal.name }}</p>
                                    <div class="flex items-center justify-between">
                                        <span v-if="deal.contact || deal.company" class="text-xs text-muted truncate">
                                            {{ deal.contact?.fullName ?? deal.company?.name }}
                                        </span>
                                        <span v-if="deal.value" class="text-xs font-medium text-secondary ml-auto shrink-0">
                                            {{ Number(deal.value).toLocaleString() }} €
                                        </span>
                                    </div>
                                    <p v-if="deal.closingDate" class="text-xs text-muted mt-1">{{ deal.closingDate }}</p>
                                </div>
                            </VueDraggable>
                        </div>
                    </div>
                </div>
            </div>

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
    </div>
</template>
