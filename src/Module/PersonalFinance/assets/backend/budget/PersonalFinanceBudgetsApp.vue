<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Pencil, Trash2, Save, X, Scale, RefreshCw } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppCheckbox from "@/shared/components/form/toggle/AppCheckbox.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useBudgetData } from "./composables/useBudgetData.js";
import { useBudgetItemsForm } from "./composables/useBudgetItemsForm.js";

const props = defineProps({
    wallets: { type: Array, required: true },
    categoriesByWallet: { type: Object, required: true },
    selectedWalletId: { type: Number, default: null },
    month: { type: String, default: null },
    sections: { type: Array, required: true },
    budgetPayload: { type: Object, default: () => ({ budget: null, sections: {}, balance: { current: "0.00", month: "0.00", rollingStart: "0.00" } }) },
    showBudgetPath: { type: String, required: true },
    createItemPath: { type: String, required: true },
    updateItemPath: { type: String, required: true },
    deleteItemPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

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
});

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

function progressPct(item) {
    const expected = parseFloat(item.expected);
    const actual = parseFloat(item.actual ?? "0");
    if (!expected || expected <= 0) return 0;
    return Math.min(100, Math.round((actual / expected) * 100));
}

function diffClass(item) {
    const diff = parseFloat(item.diff ?? "0");
    if (diff > 0) return "text-emerald-400";
    if (diff < 0) return "text-rose-400";
    return "text-muted";
}
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <div class="flex items-center gap-3">
                <AppButton variant="ghost" size="sm" v-on:click="shiftMonth(-1)">←</AppButton>
                <span class="font-mono text-sm text-primary">{{ currentMonth }}</span>
                <AppButton variant="ghost" size="sm" v-on:click="shiftMonth(1)">→</AppButton>
            </div>
            <template #actions>
                <AppButton variant="ghost" size="md" :loading="loading" v-on:click="refresh(selectedWalletId, currentMonth)">
                    <RefreshCw class="w-4 h-4" :stroke-width="2" />
                    {{ t("shared.common.refresh") }}
                </AppButton>
            </template>
        </AppListToolbar>

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
                    <div>
                        <p class="text-xs uppercase tracking-wider text-muted">{{ t("personal_finance.balance.current") }}</p>
                        <p class="font-mono text-lg" :class="parseFloat(payload.balance.current) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                            {{ payload.balance.current }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-muted">{{ t("personal_finance.balance.month") }}</p>
                        <p class="font-mono text-lg text-primary">{{ payload.balance.month }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-muted">{{ t("personal_finance.balance.rolling_start") }}</p>
                        <p class="font-mono text-lg text-primary">{{ payload.balance.rollingStart }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 relative">
                <section
                    v-for="section in sections"
                    :key="section"
                    class="bg-surface border border-line rounded-lg overflow-hidden"
                >
                    <header class="px-4 py-3 flex items-center justify-between border-b border-line bg-surface-2/40">
                        <div>
                            <h3 class="text-sm font-medium uppercase tracking-wider text-primary">
                                {{ t(`personal_finance.budget.sections.${section}`) }}
                            </h3>
                            <p class="text-xs text-muted">{{ totalsLine(sectionSummaries[section] ?? { expected: '0.00', actual: '0.00' }) }}</p>
                        </div>
                        <AppButton variant="ghost" size="sm" v-on:click="onCreate(section)">
                            <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("personal_finance.budget.add_item") }}
                        </AppButton>
                    </header>

                    <ul v-if="(sectionSummaries[section]?.items ?? []).length" class="divide-y divide-line/40">
                        <li
                            v-for="item in sectionSummaries[section].items"
                            :key="item.id"
                            class="px-4 py-3 flex items-center gap-3"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-primary truncate">{{ item.label }}</p>
                                <p class="text-xs text-muted">
                                    <span v-if="item.categoryName">{{ item.categoryName }}</span>
                                    <span v-if="parseFloat(item.carriedOver) !== 0" class="ml-2">
                                        {{ t("personal_finance.budget.carried_label", { amount: item.carriedOver }) }}
                                    </span>
                                </p>
                            </div>
                            <div class="hidden sm:flex flex-col items-end w-32">
                                <span class="font-mono text-sm text-primary">{{ item.actual ?? '0.00' }} / {{ item.expected }}</span>
                                <div class="w-full h-1 bg-line/50 rounded mt-1 overflow-hidden">
                                    <div class="h-full bg-accent-500" :style="{ width: progressPct(item) + '%' }"></div>
                                </div>
                            </div>
                            <span class="font-mono text-sm w-20 text-right" :class="diffClass(item)">{{ item.diff ?? '0.00' }}</span>
                            <div class="flex items-center gap-0.5">
                                <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="onEdit(item)">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(item)">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="px-4 py-6 text-center text-sm text-muted">
                        {{ t("personal_finance.budget.empty_section") }}
                    </p>
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
    </div>
</template>
