<script setup>
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { VueDraggable } from "vue-draggable-plus";
import { useDealsViewToggle } from "@crm/backend/deals/composables/useDealsViewToggle.js";
import { useDealsListPage } from "@crm/backend/deals/composables/useDealsListPage.js";
import { useDealsKanban } from "@crm/backend/deals/composables/useDealsKanban.js";
import { useDealsForm } from "@crm/backend/deals/composables/useDealsForm.js";
import { useDealsDelete } from "@crm/backend/deals/composables/useDealsDelete.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppDatePicker from "@/shared/components/form/AppDatePicker.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppTooltip from "@/shared/components/overlay/AppTooltip.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import { List, Columns2, Pencil, Trash2, Eye, Plus, Save, X, TrendingUp } from "lucide-vue-next";
import { stageBadge, stageBadgeBordered } from "@crm/backend/utils/deals/stageStyles.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    initialView: { type: String, default: "list" },
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
    /**
     * Extra fields to register on the create + edit forms. Lets clients extend
     * the modals + table without forking this component.
     * Example: { code: { default: '', fromEntity: (d) => d.code ?? '' } }
     */
    extraFields: { type: Object, default: () => ({}) },
});

const { localColumns, kanbanColumnsLoaded, kanbanLoading, activeStage, totalByStage, ensureKanbanColumns, updateStageForDeal, onDrop } =
    useDealsKanban(props);

const { view, setView } = useDealsViewToggle(props, ensureKanbanColumns);

const { stageOptions, items, page, totalPages, searchInput, onSearch, goToPage, reset } =
    useDealsListPage(props);

const { modal, form, errors, loading, openCreate, openEdit, submit } = useDealsForm(
    props.createPath,
    props.updatePath,
    reset,
    kanbanColumnsLoaded,
    ensureKanbanColumns,
    { extraFields: props.extraFields },
);
const { pendingDelete, deleteLoading, confirmDelete, doDelete } = useDealsDelete(props.deletePath, reset, kanbanColumnsLoaded, ensureKanbanColumns);
</script>

<template>
    <div class="flex flex-col md:flex-row gap-6">
        <nav v-if="kanbanRoutePath" class="hidden md:flex flex-col w-44 shrink-0 gap-0.5">
            <AppTooltip :title="t('backend.crm.deals.listView')" :description="t('backend.crm.deals.listView_description')" placement="right">
                <AppTab :active="view === 'list'" v-on:click="setView('list')">
                    <List class="w-4 h-4 shrink-0" :stroke-width="2" />
                    {{ t('backend.crm.deals.listView') }}
                </AppTab>
            </AppTooltip>
            <AppTooltip :title="t('backend.crm.deals.kanbanView')" :description="t('backend.crm.deals.kanbanView_description')" placement="right">
                <AppTab :active="view === 'kanban'" v-on:click="setView('kanban')">
                    <Columns2 class="w-4 h-4 shrink-0" :stroke-width="2" />
                    {{ t('backend.crm.deals.kanbanView') }}
                </AppTab>
            </AppTooltip>
        </nav>

        <div v-if="kanbanRoutePath" class="flex md:hidden gap-1 flex-wrap w-full">
            <AppTooltip :title="t('backend.crm.deals.listView')" :description="t('backend.crm.deals.listView_description')" placement="bottom">
                <AppTab :active="view === 'list'" size="sm" v-on:click="setView('list')">
                    <List class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.crm.deals.listView') }}
                </AppTab>
            </AppTooltip>
            <AppTooltip :title="t('backend.crm.deals.kanbanView')" :description="t('backend.crm.deals.kanbanView_description')" placement="bottom">
                <AppTab :active="view === 'kanban'" size="sm" v-on:click="setView('kanban')">
                    <Columns2 class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.crm.deals.kanbanView') }}
                </AppTab>
            </AppTooltip>
        </div>

        <div class="flex-1 min-w-0 space-y-4">
            <!-- LIST VIEW -->
            <div v-show="view === 'list'" class="space-y-4">
                <AppListToolbar>
                    <AppSearchInput
                        v-model="searchInput"
                        :placeholder="t('backend.crm.deals.searchPlaceholder')"
                        v-on:search="onSearch"
                    />
                    <template #actions>
                        <AppButton
                            v-if="can('crm.deals.create')"
                            variant="primary"
                            size="md"
                            class="w-full sm:w-auto"
                            v-on:click="openCreate"
                        >
                            <Plus class="w-4 h-4" :stroke-width="2" />
                            {{ t('backend.crm.deals.add') }}
                        </AppButton>
                    </template>
                </AppListToolbar>
                <div class="sm:hidden space-y-3">
                    <div v-for="deal in items" :key="deal.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-primary">{{ deal.name }}</p>
                                <p v-if="deal.contact || deal.company" class="text-xs text-muted mt-0.5">{{ deal.contact?.fullName ?? deal.company?.name }}</p>
                            </div>
                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium shrink-0', stageBadge(deal.stage)]">
                                {{ t(`backend.crm.deals.stages.${deal.stage}`) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-line">
                            <span class="text-xs text-secondary font-medium">{{ deal.value ? `${Number(deal.value).toLocaleString()} €` : '—' }}</span>
                            <div class="flex items-center gap-0.5">
                                <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: deal.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('crm.deals.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(deal)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('crm.deals.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(deal)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-2/50 border-b border-line/40">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.deals.name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.deals.stage') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.crm.deals.contact') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('backend.crm.deals.value') }}</th>
                                <slot name="extra-headers" />
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/40">
                            <tr v-for="deal in items" :key="deal.id" class="group hover:bg-surface-2/40 transition-colors">
                                <td class="px-6 py-3 font-medium text-primary">{{ deal.name }}</td>
                                <td class="px-6 py-3">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', stageBadge(deal.stage)]">
                                        {{ t(`backend.crm.deals.stages.${deal.stage}`) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ deal.contact?.fullName ?? deal.company?.name ?? '—' }}</td>
                                <td class="px-6 py-3 text-secondary hidden lg:table-cell">{{ deal.value ? `${Number(deal.value).toLocaleString()} €` : '—' }}</td>
                                <slot name="extra-cells" :deal="deal" />
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: deal.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                        <AppIconButton v-if="can('crm.deals.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(deal)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                        <AppIconButton v-if="can('crm.deals.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(deal)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!items?.length">
                                <td :colspan="5" class="px-6 py-8 text-center text-sm text-muted">{{ t('backend.crm.deals.empty') }}</td>
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
                                {{ t(`backend.crm.deals.stages.${stage}`) }}
                                <span :class="['inline-flex items-center justify-center min-w-4 h-4 rounded-full px-1 text-xs', activeStage === stage ? 'bg-white/20' : 'bg-surface-3 text-muted']">
                                    {{ localColumns[stage]?.length ?? 0 }}
                                </span>
                            </AppTab>
                        </div>

                        <div class="mt-3 space-y-2">
                            <p v-if="!localColumns[activeStage]?.length" class="py-8 text-center text-sm text-muted">
                                {{ t('backend.crm.deals.empty') }}
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
                                        <option v-for="s in stages" :key="s" :value="s">{{ t(`backend.crm.deals.stages.${s}`) }}</option>
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
                                <span>{{ t(`backend.crm.deals.stages.${stage}`) }}</span>
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

            <AppModal
                :show="modal.open"
                :title="modal.entity ? t('backend.crm.deals.edit', { name: modal.entity.name ?? '' }) : t('backend.crm.deals.create')"
                :icon="modal.entity ? Pencil : TrendingUp"
                :closeable="false"
                v-on:close="modal.open = false"
            >
                <form class="space-y-4" v-on:submit.prevent="submit">
                    <AppInput
                        v-model="form.name"
                        :label="t('backend.crm.deals.name')"
                        :placeholder="t('backend.crm.deals.namePlaceholder')"
                        :error="errors.name"
                        required
                    />
                    <AppSelect v-model="form.stage" :label="t('backend.crm.deals.stage')">
                        <option v-for="opt in stageOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </AppSelect>
                    <AppInput v-model="form.value" :label="t('backend.crm.deals.value')" :placeholder="t('backend.crm.deals.valuePlaceholder')" />
                    <AppDatePicker v-model="form.closingDate" :label="t('backend.crm.deals.closingDate')" />
                    <slot name="extra-form-fields" :form="form" :errors="errors" :deal="modal.entity" />
                </form>
                <template #footer>
                    <AppModalFooter>
                        <AppButton variant="ghost" size="md" type="button" v-on:click="modal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                        <AppButton variant="primary" size="md" type="submit" :loading="loading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
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
                <p class="text-sm text-primary">{{ t('backend.crm.deals.deleteConfirm', { name: pendingDelete?.name ?? '' }) }}</p>
                <p class="text-sm text-secondary">{{ t('backend.crm.deals.deleteWarning') }}</p>
                <template #footer>
                    <AppModalFooter>
                        <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                        <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                    </AppModalFooter>
                </template>
            </AppModal>
        </div>
    </div>
</template>
