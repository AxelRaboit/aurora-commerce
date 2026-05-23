<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { Plus, Pencil, Trash2, Save, X, Scale, RefreshCw, Receipt, List, ChevronLeft, ChevronRight, AlertTriangle, Wallet, TrendingUp, Clock, FileDown, ClipboardList, Play, Bookmark, RotateCcw } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppCheckbox from "@/shared/components/form/toggle/AppCheckbox.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import PersonalFinanceTransactionCreateModal from "../transaction/components/PersonalFinanceTransactionCreateModal.vue";
import PersonalFinanceBudgetItemTransactionsModal from "./components/PersonalFinanceBudgetItemTransactionsModal.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import { useBudgetData } from "./composables/useBudgetData.js";
import { useBudgetItemsForm } from "./composables/useBudgetItemsForm.js";
import { useBudgetQuickAdd } from "./composables/useBudgetQuickAdd.js";
import { useBudgetSectionTheme } from "./composables/useBudgetSectionTheme.js";
import { useBudgetProgress } from "./composables/useBudgetProgress.js";
import { useBudgetPresetHooks } from "./composables/useBudgetPresetHooks.js";
import { useBudgetMonthReset } from "./composables/useBudgetMonthReset.js";

const props = defineProps({
    wallets: { type: Array, required: true },
    categoriesByWallet: { type: Object, required: true },
    selectedWalletId: { type: Number, default: null },
    month: { type: String, default: null },
    sections: { type: Array, required: true },
    types: { type: Array, default: () => [] },
    budgetPayload: { type: Object, default: () => ({ budget: null, sections: {}, balance: { current: "0.00", month: "0.00", rollingStart: "0.00" } }) },
    showBudgetPath: { type: String, required: true },
    exportBudgetPath: { type: String, required: true },
    resetBudgetPath: { type: String, required: true },
    savePresetPath: { type: String, default: null },
    listPresetsPath: { type: String, default: null },
    applyPresetPath: { type: String, default: null },
    createItemPath: { type: String, required: true },
    updateItemPath: { type: String, required: true },
    deleteItemPath: { type: String, required: true },
    createTransactionPath: { type: String, required: true },
    updateTransactionPath: { type: String, required: true },
    deleteTransactionPath: { type: String, required: true },
    itemTransactionsPath: { type: String, required: true },
    uploadAttachmentPath: { type: String, required: true },
    deleteAttachmentPath: { type: String, required: true },
    serveAttachmentPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { formatMonthYear } = useDateFormat();

const selectedWalletId = ref(props.selectedWalletId ?? props.wallets[0]?.id ?? null);
const currentMonth = ref(props.month ?? new Date().toISOString().slice(0, 7));

const { payload, loading, refresh } = useBudgetData(props.showBudgetPath, props.budgetPayload);

watch([selectedWalletId, currentMonth], async ([wid, m]) => {
    await refresh(wid, m);
});

const walletOptions = computed(() => props.wallets.map((w) => ({ value: w.id, label: w.name })));

const currentCategoryOptions = computed(() => {
    if (!selectedWalletId.value) return [{ value: null, label: t("personal_finance.budget.no_category") }];
    const list = props.categoriesByWallet[String(selectedWalletId.value)] ?? [];
    return [
        { value: null, label: t("personal_finance.budget.no_category") },
        ...list.map((c) => ({ value: c.id, label: c.name })),
    ];
});

const sectionOptions = computed(() =>
    props.sections.map((s) => ({
        value: s,
        label: t(`personal_finance.budget.sections.${s}`),
    })),
);

const sectionSummaries = computed(() => payload.value.sections ?? {});

function shiftMonth(delta) {
    const [year, month] = currentMonth.value.split("-").map(Number);
    const date = new Date(Date.UTC(year, month - 1 + delta, 1));
    currentMonth.value = `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, "0")}`;
}

const {
    show: showItemModal,
    isEditing: itemEditing,
    form: itemForm,
    errors: itemErrors,
    loading: itemLoading,
    openCreate: openItemCreate,
    openEdit: openItemEdit,
    submit: submitItem,
    pendingDelete,
    deleteLoading,
    confirmDelete,
    doDelete,
} = useBudgetItemsForm({
    createPath: props.createItemPath,
    updatePath: props.updateItemPath,
    deletePath: props.deleteItemPath,
    onChanged: () => refresh(selectedWalletId.value, currentMonth.value),
    extraFields: props.extraFields,
});

const { createModalRef, onQuickAdd } = useBudgetQuickAdd({ selectedWalletId, currentMonth });
const {
    headerClasses: sectionHeaderClasses,
    titleClasses: sectionTitleClasses,
    barClass: sectionBarClass,
    icon: sectionIcon,
} = useBudgetSectionTheme();
const { progressPct, isOverrun, diffPillClasses } = useBudgetProgress();

function sectionSummary(section) {
    return sectionSummaries.value[section] ?? { expected: "0.00", actual: "0.00", items: [] };
}
const listModalRef = ref(null);

function onListTransactions(item) {
    if (!item?.categoryId) return;
    listModalRef.value?.open(item);
}

function onCreate(section) {
    openItemCreate({
        walletId: selectedWalletId.value,
        month: currentMonth.value,
        section,
    });
}

function onEdit(item) {
    openItemEdit({
        walletId: selectedWalletId.value,
        month: currentMonth.value,
        item,
    });
}

function totalsLine(summary) {
    return t("personal_finance.budget.totals_line", {
        planned: summary.expected,
        actual: summary.actual,
    });
}

/**
 * Trigger an XLSX download for the currently-displayed budget month.
 * Real navigation lets the browser handle `Content-Disposition:
 * attachment` rather than the SPA router.
 */
function exportBudgetXlsx() {
    if (!selectedWalletId.value) return;
    const url = new URL(props.exportBudgetPath.replace("__walletId__", selectedWalletId.value), window.location.origin);
    if (currentMonth.value) url.searchParams.set("month", currentMonth.value);
    window.location.assign(url.toString());
}

const presetsEnabled = computed(() => !!props.savePresetPath && !!props.listPresetsPath && !!props.applyPresetPath);

const {
    showSave: showSavePreset,
    saveForm: presetSaveForm,
    saveErrors: presetSaveErrors,
    openSave: openSavePreset,
    submitSave: submitSavePreset,
    showApply: showApplyPreset,
    presetList,
    selectedPresetId: applyPresetId,
    applyMode: applyPresetMode,
    applyErrors: applyPresetErrors,
    openApply: openApplyPreset,
    submitApply: submitApplyPreset,
    loading: presetLoading,
} = useBudgetPresetHooks({
    savePresetPath: props.savePresetPath,
    listPresetsPath: props.listPresetsPath,
    applyPresetPath: props.applyPresetPath,
    onApplied: () => refresh(selectedWalletId.value, currentMonth.value),
});

const {
    show: showReset,
    clearBudget: resetClearBudget,
    cascade: resetCascade,
    loading: resetLoading,
    open: openReset,
    confirm: confirmReset,
} = useBudgetMonthReset({
    resetPath: props.resetBudgetPath,
    monthRef: currentMonth,
    onReset: () => refresh(selectedWalletId.value, currentMonth.value),
});

</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <div class="flex items-center justify-center sm:justify-start gap-2">
                <AppIconButton :title="t('shared.common.previous')" v-on:click="shiftMonth(-1)">
                    <ChevronLeft class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <span class="text-sm text-primary font-medium tabular-nums min-w-[9rem] text-center">{{ formatMonthYear(currentMonth) }}</span>
                <AppIconButton :title="t('shared.common.next')" v-on:click="shiftMonth(1)">
                    <ChevronRight class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
            </div>
            <template #actions>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <AppButton
                        v-if="presetsEnabled"
                        variant="ghost"
                        size="md"
                        :disabled="!selectedWalletId"
                        v-on:click="openApplyPreset(selectedWalletId)"
                    >
                        <Play class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.budget_presets.apply") }}
                    </AppButton>
                    <AppButton
                        v-if="presetsEnabled"
                        variant="ghost"
                        size="md"
                        :disabled="!selectedWalletId"
                        v-on:click="openSavePreset"
                    >
                        <Bookmark class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.budget_presets.save_from_month") }}
                    </AppButton>
                    <AppButton
                        variant="ghost"
                        size="md"
                        :disabled="!selectedWalletId"
                        v-on:click="exportBudgetXlsx"
                    >
                        <FileDown class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.budget.export_xlsx") }}
                    </AppButton>
                    <AppButton
                        variant="danger"
                        size="md"
                        :disabled="!selectedWalletId"
                        v-on:click="openReset"
                    >
                        <RotateCcw class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.budget.reset_button") }}
                    </AppButton>
                    <AppButton variant="ghost" size="md" :loading="loading" v-on:click="refresh(selectedWalletId, currentMonth)">
                        <RefreshCw class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.common.refresh") }}
                    </AppButton>
                </div>
            </template>
        </AppListToolbar>

        <AppMessage variant="info">
            {{ t("personal_finance.budget.help") }}
        </AppMessage>

        <section v-if="!wallets.length" class="bg-surface border border-line rounded-lg p-6 text-muted text-sm">
            {{ t("personal_finance.budget.no_wallet") }}
        </section>

        <template v-else>
            <div class="bg-surface border border-line rounded-lg p-4 space-y-4">
                <AppMultiselect
                    v-model="selectedWalletId"
                    :label="t('personal_finance.transactions.fields.wallet')"
                    :options="walletOptions"
                    :allow-empty="false"
                />

                <div v-if="selectedWalletId" class="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t border-line pt-3">
                    <div class="bg-surface-2/40 border border-line rounded-md p-3">
                        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                            <Wallet class="w-3.5 h-3.5" :stroke-width="2" />
                            <span>{{ t("personal_finance.balance.current") }}</span>
                        </div>
                        <p class="font-mono text-lg mt-1" :class="parseFloat(payload.balance.current) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                            {{ payload.balance.current }}
                        </p>
                    </div>
                    <div class="bg-surface-2/40 border border-line rounded-md p-3">
                        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                            <TrendingUp class="w-3.5 h-3.5" :stroke-width="2" />
                            <span>{{ t("personal_finance.balance.month") }}</span>
                        </div>
                        <p class="font-mono text-lg text-primary mt-1">{{ payload.balance.month }}</p>
                    </div>
                    <div class="bg-surface-2/40 border border-line rounded-md p-3">
                        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                            <Clock class="w-3.5 h-3.5" :stroke-width="2" />
                            <span>{{ t("personal_finance.balance.rolling_start") }}</span>
                        </div>
                        <p class="font-mono text-lg text-primary mt-1">{{ payload.balance.rollingStart }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 relative">
                <section
                    v-for="section in sections"
                    :key="section"
                    class="bg-surface border border-line rounded-lg overflow-hidden"
                >
                    <header class="px-4 py-3 flex items-center justify-between gap-4 border-b border-line" :class="sectionHeaderClasses(section)">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-medium uppercase tracking-wider flex items-center gap-2" :class="sectionTitleClasses(section)">
                                <component :is="sectionIcon(section)" class="w-4 h-4 shrink-0" :stroke-width="2" />
                                <span class="truncate">{{ t(`personal_finance.budget.sections.${section}`) }}</span>
                            </h3>
                            <p class="text-xs text-muted mt-0.5">{{ totalsLine(sectionSummary(section)) }}</p>
                            <div
                                class="hidden sm:block w-full h-1 bg-line/50 rounded mt-2 overflow-hidden"
                                :title="t('personal_finance.budget.section_progress_title')"
                            >
                                <div
                                    class="h-full transition-all"
                                    :class="isOverrun(sectionSummary(section)) ? 'bg-rose-500' : sectionBarClass(section)"
                                    :style="{ width: progressPct(sectionSummary(section)) + '%' }"
                                ></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <slot name="extra-headers" :section="section" />
                            <AppButton variant="ghost" size="sm" :title="t('personal_finance.budget.add_item')" v-on:click="onCreate(section)">
                                <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                                <span class="hidden sm:inline">{{ t("personal_finance.budget.add_item") }}</span>
                            </AppButton>
                        </div>
                    </header>

                    <ul v-if="sectionSummary(section).items.length" class="divide-y divide-line/40">
                        <li
                            v-for="item in sectionSummary(section).items"
                            :key="item.id"
                            class="px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-primary truncate">{{ item.label }}</p>
                                <div class="text-xs text-muted flex items-center gap-2 flex-wrap mt-0.5">
                                    <span v-if="item.categoryName">{{ item.categoryName }}</span>
                                    <span v-if="parseFloat(item.carriedOver) !== 0" class="px-1.5 py-0.5 bg-amber-500/15 text-amber-400 rounded text-xs">
                                        {{ t("personal_finance.budget.carried_label", { amount: item.carriedOver }) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex flex-col items-stretch sm:items-end w-full sm:w-32">
                                <div class="flex items-center justify-between sm:justify-end gap-1.5">
                                    <AlertTriangle
                                        v-if="isOverrun(item)"
                                        class="w-3.5 h-3.5 text-rose-400 shrink-0"
                                        :stroke-width="2"
                                        :title="t('personal_finance.budget.overrun_warning')"
                                    />
                                    <span class="font-mono text-sm text-primary">{{ item.actual ?? '0.00' }} / {{ item.expected }}</span>
                                </div>
                                <div class="w-full h-1 bg-line/50 rounded mt-1 overflow-hidden">
                                    <div
                                        class="h-full transition-all"
                                        :class="isOverrun(item) ? 'bg-rose-500' : sectionBarClass(section)"
                                        :style="{ width: progressPct(item) + '%' }"
                                    ></div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end gap-2 sm:gap-0.5">
                                <span class="font-mono text-xs px-2 py-0.5 rounded-full" :class="diffPillClasses(item)">{{ item.diff ?? '0.00' }}</span>
                                <slot name="extra-cells" :item="item" />
                                <div class="flex items-center gap-0.5">
                                    <AppIconButton color="emerald" :title="t('personal_finance.budget.quick_add_transaction')" v-on:click="onQuickAdd(item, section)">
                                        <Receipt class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton
                                        color="sky"
                                        :title="item.categoryId ? t('personal_finance.budget.list_transactions') : t('personal_finance.budget.list_transactions_disabled')"
                                        :disabled="!item.categoryId"
                                        v-on:click="onListTransactions(item)"
                                    >
                                        <List class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="onEdit(item)">
                                        <Pencil class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(item)">
                                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="px-4 py-8 text-center">
                        <p class="text-sm text-muted mb-3">{{ t("personal_finance.budget.empty_section") }}</p>
                        <AppButton variant="ghost" size="sm" v-on:click="onCreate(section)">
                            <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("personal_finance.budget.empty_section_cta") }}
                        </AppButton>
                    </div>
                </section>
                <AppLoader :active="loading" />
            </div>
        </template>

        <AppModal
            :show="showItemModal"
            :title="itemEditing ? t('personal_finance.budget.edit_item') : t('personal_finance.budget.add_item')"
            :icon="Scale"
            :closeable="false"
            v-on:close="showItemModal = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitItem">
                <AppMultiselect
                    v-model="itemForm.section"
                    :label="t('personal_finance.budget.fields.section')"
                    :options="sectionOptions"
                    :allow-empty="false"
                    required
                />
                <AppInput
                    v-model="itemForm.label"
                    :label="t('personal_finance.budget.fields.label')"
                    :placeholder="t('personal_finance.budget.placeholders.label')"
                    :error="itemErrors.label"
                    required
                />
                <AppAmountInput
                    v-model="itemForm.plannedAmount"
                    :label="t('personal_finance.budget.fields.planned')"
                    :placeholder="t('personal_finance.transactions.placeholders.amount')"
                    :error="itemErrors.plannedAmount"
                    required
                />
                <AppAmountInput
                    v-model="itemForm.carriedOver"
                    :label="t('personal_finance.budget.fields.carried')"
                    :placeholder="'0.00'"
                    :error="itemErrors.carriedOver"
                />
                <AppMultiselect
                    v-model="itemForm.categoryId"
                    :label="t('personal_finance.budget.fields.category')"
                    :placeholder="t('personal_finance.transactions.placeholders.category')"
                    :options="currentCategoryOptions"
                    :allow-empty="true"
                />
                <AppInput
                    v-model="itemForm.notes"
                    :label="t('personal_finance.budget.fields.notes')"
                    :placeholder="t('personal_finance.budget.placeholders.notes')"
                />
                <AppCheckbox
                    v-model="itemForm.repeatNextMonth"
                    :label="t('personal_finance.budget.fields.repeat_next_month')"
                />
                <slot name="extra-form-fields" :form="itemForm" :errors="itemErrors" :editing="itemEditing" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showItemModal = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="itemLoading" v-on:click="submitItem">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <PersonalFinanceTransactionCreateModal
            ref="createModalRef"
            :wallets="wallets"
            :categories-by-wallet="categoriesByWallet"
            :types="types"
            :create-path="createTransactionPath"
            :extra-fields="extraFields"
            v-on:created="refresh(selectedWalletId, currentMonth)"
        />

        <PersonalFinanceBudgetItemTransactionsModal
            ref="listModalRef"
            :categories-by-wallet="categoriesByWallet"
            :types="types"
            :item-transactions-path="itemTransactionsPath"
            :update-transaction-path="updateTransactionPath"
            :delete-transaction-path="deleteTransactionPath"
            :upload-attachment-path="uploadAttachmentPath"
            :delete-attachment-path="deleteAttachmentPath"
            :serve-attachment-path="serveAttachmentPath"
            :extra-fields="extraFields"
            v-on:changed="refresh(selectedWalletId, currentMonth)"
        />

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t("personal_finance.budget.delete_confirm") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Save current month as a preset -->
        <AppModal
            v-if="presetsEnabled"
            :show="showSavePreset"
            :title="t('personal_finance.budget_presets.save_from_month')"
            :icon="Bookmark"
            :closeable="false"
            max-width="md"
            v-on:close="showSavePreset = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitSavePreset(selectedWalletId, currentMonth)">
                <AppInput
                    v-model="presetSaveForm.name"
                    :label="`${t('personal_finance.budget_presets.fields.name')} *`"
                    :placeholder="t('personal_finance.budget_presets.placeholders.name_for_saved')"
                    :error="presetSaveErrors.name"
                />
                <AppInput
                    v-model="presetSaveForm.description"
                    :label="t('personal_finance.budget_presets.fields.description')"
                    :placeholder="t('personal_finance.budget_presets.placeholders.description')"
                    :error="presetSaveErrors.description"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showSavePreset = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" :loading="presetLoading" v-on:click="submitSavePreset(selectedWalletId, currentMonth)">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Apply a preset to the current month -->
        <AppModal
            v-if="presetsEnabled"
            :show="showApplyPreset"
            :title="t('personal_finance.budget_presets.apply')"
            :icon="Play"
            :closeable="false"
            max-width="md"
            v-on:close="showApplyPreset = false"
        >
            <div class="space-y-4">
                <p v-if="!presetList.length" class="text-sm text-muted">
                    {{ t("personal_finance.budget_presets.empty") }}
                </p>
                <template v-else>
                    <p class="text-sm text-muted">{{ t("personal_finance.budget_presets.apply_help") }}</p>
                    <ul class="space-y-1.5 max-h-64 overflow-y-auto">
                        <li
                            v-for="preset in presetList"
                            :key="preset.id"
                            class="flex items-start gap-3 p-2 rounded-md cursor-pointer transition-colors"
                            :class="applyPresetId === preset.id ? 'bg-accent-500/10 border border-accent-500/30' : 'border border-transparent hover:bg-surface-2'"
                            v-on:click="applyPresetId = preset.id"
                        >
                            <ClipboardList class="w-4 h-4 mt-0.5 text-muted shrink-0" :stroke-width="2" />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-primary truncate">{{ preset.name }}</p>
                                <p v-if="preset.description" class="text-xs text-muted line-clamp-1">{{ preset.description }}</p>
                                <p class="text-xs text-muted">{{ t("personal_finance.budget_presets.summary", { count: preset.itemCount ?? 0 }, preset.itemCount ?? 0) }}</p>
                            </div>
                        </li>
                    </ul>
                    <p v-if="applyPresetErrors.preset" class="text-xs text-rose-400">{{ applyPresetErrors.preset }}</p>

                    <div class="border-t border-line pt-3">
                        <label class="block text-xs uppercase tracking-wider text-muted mb-2">{{ t("personal_finance.budget_presets.fields.mode") }}</label>
                        <div class="flex items-center gap-2">
                            <AppTab variant="pill" size="sm" :active="applyPresetMode === 'append'" v-on:click="applyPresetMode = 'append'">
                                {{ t("personal_finance.budget_presets.modes.append") }}
                            </AppTab>
                            <AppTab variant="pill" size="sm" :active="applyPresetMode === 'replace'" v-on:click="applyPresetMode = 'replace'">
                                {{ t("personal_finance.budget_presets.modes.replace") }}
                            </AppTab>
                        </div>
                        <p class="text-xs text-muted mt-2">{{ t(`personal_finance.budget_presets.mode_descriptions.${applyPresetMode}`) }}</p>
                    </div>
                </template>
            </div>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showApplyPreset = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        :disabled="!presetList.length || !applyPresetId"
                        :loading="presetLoading"
                        v-on:click="submitApplyPreset(currentMonth)"
                    >
                        <Play class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("personal_finance.budget_presets.apply") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Reset month confirmation -->
        <AppModal
            :show="showReset"
            :title="t('personal_finance.budget.reset_title')"
            :icon="AlertTriangle"
            :closeable="false"
            max-width="md"
            v-on:close="showReset = false"
        >
            <div class="space-y-4">
                <p class="text-sm text-primary">
                    <template v-if="resetCascade">
                        {{ t("personal_finance.budget.reset_confirm_cascade", { from: formatMonthYear(currentMonth) }) }}
                    </template>
                    <template v-else>
                        {{ t("personal_finance.budget.reset_confirm", { month: formatMonthYear(currentMonth) }) }}
                    </template>
                </p>
                <p class="text-xs text-muted">{{ t("personal_finance.budget.reset_help") }}</p>
                <AppCheckbox
                    v-model="resetCascade"
                    :label="t('personal_finance.budget.reset_cascade')"
                />
                <AppCheckbox
                    v-model="resetClearBudget"
                    :label="t('personal_finance.budget.reset_clear_budget')"
                />
            </div>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="showReset = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="resetLoading" v-on:click="confirmReset(selectedWalletId)">
                        <RotateCcw class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("personal_finance.budget.reset_confirm_button") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
