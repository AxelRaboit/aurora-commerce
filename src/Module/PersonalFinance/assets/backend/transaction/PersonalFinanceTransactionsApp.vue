<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Pencil, Trash2, Save, X, Receipt, ArrowRightLeft, Split as SplitIcon, Paperclip, Scale, Wallet, FileDown } from "lucide-vue-next";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppDatePicker from "@/shared/components/form/picker/AppDatePicker.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import PersonalFinanceTransactionCreateModal from "./components/PersonalFinanceTransactionCreateModal.vue";
import PersonalFinanceTransactionEditModal from "./components/PersonalFinanceTransactionEditModal.vue";
import { useTransfersForm } from "./composables/useTransfersForm.js";
import { useTransfersDelete } from "./composables/useTransfersDelete.js";
import { useSplitsCreate } from "./composables/useSplitsCreate.js";
import { useSplitsDelete } from "./composables/useSplitsDelete.js";
import { useWalletBalance } from "./composables/useWalletBalance.js";
import { useBalanceAdjustment } from "./composables/useBalanceAdjustment.js";

const props = defineProps({
    wallets: { type: Array, required: true },
    categoriesByWallet: { type: Object, required: true },
    selectedWalletId: { type: Number, default: null },
    transactions: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    listPath: { type: String, required: true },
    exportPath: { type: String, required: true },
    types: { type: Array, required: true },
    createTransactionPath: { type: String, required: true },
    updateTransactionPath: { type: String, required: true },
    deleteTransactionPath: { type: String, required: true },
    createTransferPath: { type: String, required: true },
    updateTransferPath: { type: String, required: true },
    deleteTransferPath: { type: String, required: true },
    showTransferPath: { type: String, required: true },
    createSplitPath: { type: String, required: true },
    deleteSplitPath: { type: String, required: true },
    uploadAttachmentPath: { type: String, required: true },
    deleteAttachmentPath: { type: String, required: true },
    serveAttachmentPath: { type: String, required: true },
    walletBalancePath: { type: String, required: true },
    walletBalanceAdjustPath: { type: String, required: true },
    balance: { type: Object, default: () => ({ current: "0.00", month: "0.00", rollingStart: "0.00" }) },
    balanceMonth: { type: String, default: null },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const selectedWalletId = ref(props.selectedWalletId ?? props.wallets[0]?.id ?? null);
const activeTag = ref(null);

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.transactions,
        extraParams: () => ({ walletId: selectedWalletId.value, tag: activeTag.value || undefined }),
    },
);

watch(selectedWalletId, () => reset());

function filterByTag(tag) {
    activeTag.value = tag;
    reset();
}

function clearTagFilter() {
    activeTag.value = null;
    reset();
}

const walletOptions = computed(() =>
    props.wallets.map((w) => ({ value: w.id, label: w.name })),
);

const typeOptions = computed(() =>
    props.types.map((ty) => ({ value: ty, label: t(`personal_finance.transactions.types.${ty}`) })),
);

const currentCategoryOptions = computed(() => {
    if (!selectedWalletId.value) return [{ value: null, label: t("personal_finance.transactions.uncategorized") }];
    const list = props.categoriesByWallet[String(selectedWalletId.value)] ?? [];
    return [
        { value: null, label: t("personal_finance.transactions.uncategorized") },
        ...list.map((c) => ({ value: c.id, label: c.name })),
    ];
});

const createModalRef = ref(null);
const editModalRef = ref(null);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deleteTransactionPath,
    () => refreshAfterTx(),
    "personal_finance.transactions.deleted",
);

const {
    show: showTransfer,
    isEditing: transferEditing,
    form: transferForm,
    errors: transferErrors,
    loading: transferLoading,
    openCreate: openTransferCreate,
    openEdit: openTransferEdit,
    submit: submitTransfer,
} = useTransfersForm(
    props.createTransferPath,
    props.updateTransferPath,
    props.showTransferPath,
    () => refreshAfterTx(),
    { extraFields: props.extraFields },
);

const {
    pendingDelete: pendingTransferDelete,
    loading: transferDeleteLoading,
    confirm: confirmTransferDelete,
    submit: doTransferDelete,
} = useTransfersDelete(props.deleteTransferPath, () => reset());

const toWalletOptionsForTransfer = computed(() =>
    props.wallets
        .filter((w) => w.id !== transferForm.value.fromWalletId)
        .map((w) => ({ value: w.id, label: w.name })),
);

const splitTotal = computed(() => {
    let total = 0;
    for (const part of splitForm.value.parts) {
        const n = parseFloat(part.amount);
        if (!isNaN(n)) total += n;
    }
    return total.toFixed(2);
});

const { balance, refresh: refreshBalance } = useWalletBalance(props.walletBalancePath, props.balance);

const {
    show: showAdjust,
    form: adjustForm,
    errors: adjustErrors,
    loading: adjustLoading,
    open: openAdjust,
    submit: submitAdjust,
} = useBalanceAdjustment(props.walletBalanceAdjustPath, () => {
    reset();
    refreshBalance(selectedWalletId.value);
});

watch(selectedWalletId, (id) => {
    if (id) refreshBalance(id);
});

function refreshAfterTx() {
    reset();
    refreshBalance(selectedWalletId.value);
}

const {
    show: showSplit,
    form: splitForm,
    errors: splitErrors,
    loading: splitLoading,
    open: openSplitCreate,
    addPart: addSplitPart,
    removePart: removeSplitPart,
    submit: submitSplit,
} = useSplitsCreate(props.createSplitPath, () => reset());

const {
    pendingDelete: pendingSplitDelete,
    loading: splitDeleteLoading,
    confirm: confirmSplitDelete,
    submit: doSplitDelete,
} = useSplitsDelete(props.deleteSplitPath, () => reset());

function isTransferLeg(tx) {
    return !!tx?.transferId;
}

function isSplitLeg(tx) {
    return !!tx?.splitId;
}

function onEditRow(tx) {
    if (isTransferLeg(tx)) {
        openTransferEdit(tx.transferId);
        return;
    }
    if (isSplitLeg(tx)) {
        return;
    }
    editModalRef.value?.open(tx);
}

function onDeleteRow(tx) {
    if (isTransferLeg(tx)) {
        confirmTransferDelete({ transferId: tx.transferId, date: tx.date, amount: tx.amount });
        return;
    }
    if (isSplitLeg(tx)) {
        confirmSplitDelete({ splitId: tx.splitId });
        return;
    }
    confirmDelete(tx);
}

function formatType(type) {
    return t(`personal_finance.transactions.types.${type}`);
}

function formatAmount(transaction) {
    const sign = transaction.type === "income" ? "+" : "-";
    return `${sign}${transaction.amount}`;
}

function describeTx(tx) {
    if (!tx) return "";
    return `${formatDateShort(tx.date)} · ${formatType(tx.type)} ${formatAmount(tx)}`;
}

/**
 * Open the XLSX export endpoint with the current filters. Bypasses
 * the SPA-router by triggering a real navigation — the streamed
 * response has `Content-Disposition: attachment`, so the browser
 * downloads the file rather than rendering it.
 */
function exportXlsx() {
    if (!selectedWalletId.value) return;
    const url = new URL(props.exportPath.replace("__walletId__", selectedWalletId.value), window.location.origin);
    if (searchInput.value) url.searchParams.set("search", searchInput.value);
    if (activeTag.value) url.searchParams.set("tag", activeTag.value);
    window.location.assign(url.toString());
}
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('personal_finance.transactions.search_placeholder')"
                v-on:search="onSearch"
            />
            <template #actions>
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <AppButton
                        variant="ghost"
                        size="md"
                        :disabled="!selectedWalletId"
                        :title="t('personal_finance.transactions.export_xlsx')"
                        v-on:click="exportXlsx"
                    >
                        <FileDown class="w-4 h-4" :stroke-width="2" />
                        <span class="hidden sm:inline">{{ t("personal_finance.transactions.export_xlsx") }}</span>
                    </AppButton>
                    <AppButton
                        variant="ghost"
                        size="md"
                        :disabled="!selectedWalletId || currentCategoryOptions.length <= 1"
                        v-on:click="openSplitCreate(selectedWalletId)"
                    >
                        <SplitIcon class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.splits.add") }}
                    </AppButton>
                    <AppButton
                        variant="secondary"
                        size="md"
                        :disabled="wallets.length < 2"
                        v-on:click="openTransferCreate(selectedWalletId)"
                    >
                        <ArrowRightLeft class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.transfers.add") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        :disabled="!selectedWalletId"
                        v-on:click="createModalRef?.open(selectedWalletId)"
                    >
                        <Plus class="w-4 h-4" :stroke-width="2" />
                        {{ t("personal_finance.transactions.add") }}
                    </AppButton>
                </div>
            </template>
        </AppListToolbar>

        <AppMessage variant="info">
            {{ t("personal_finance.transactions.help") }}
        </AppMessage>

        <div v-if="activeTag" class="flex items-center gap-2">
            <span class="text-xs text-muted">{{ t("personal_finance.transactions.filtered_by_tag") }}</span>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs bg-accent-500/15 text-accent-400 hover:bg-accent-500/25 transition-colors"
                v-on:click="clearTagFilter"
            >
                <span>#{{ activeTag }}</span>
                <X class="w-3 h-3" :stroke-width="2" />
            </button>
        </div>

        <section v-if="!wallets.length" class="bg-surface border border-line rounded-lg p-6 text-muted text-sm">
            {{ t("personal_finance.transactions.no_wallet") }}
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
                        <p class="font-mono text-lg" :class="parseFloat(balance.current) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                            {{ balance.current }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wider text-muted">{{ t("personal_finance.balance.month") }}</p>
                        <p class="font-mono text-lg text-primary">{{ balance.month }}</p>
                    </div>
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-muted">{{ t("personal_finance.balance.rolling_start") }}</p>
                            <p class="font-mono text-lg text-primary">{{ balance.rollingStart }}</p>
                        </div>
                        <AppButton variant="ghost" size="sm" v-on:click="openAdjust(selectedWalletId)">
                            <Scale class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("personal_finance.balance_adjustment.open") }}
                        </AppButton>
                    </div>
                </div>
            </div>

            <div class="relative space-y-4">
                <div v-if="!items?.length" class="bg-surface border border-line rounded-lg p-6 text-center text-sm text-muted">
                    {{ t("personal_finance.transactions.empty") }}
                </div>

                <div v-else class="sm:hidden space-y-3">
                    <div v-for="tx in items" :key="tx.id" class="bg-surface border border-line rounded-lg p-4 space-y-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-primary truncate flex items-center gap-1.5">
                                    <Paperclip v-if="tx.hasAttachment" class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" />
                                    <ArrowRightLeft v-if="isTransferLeg(tx)" class="w-3.5 h-3.5 text-sky-400 shrink-0" :stroke-width="2" :title="t('personal_finance.transfers.leg_badge_title')" />
                                    <SplitIcon v-else-if="isSplitLeg(tx)" class="w-3.5 h-3.5 text-amber-400 shrink-0" :stroke-width="2" :title="t('personal_finance.splits.leg_badge_title')" />
                                    <Receipt v-else class="w-3.5 h-3.5 text-accent-400 shrink-0" :stroke-width="2" :title="t('personal_finance.transactions.leg_badge_title')" />
                                    <span>{{ tx.description ?? t("personal_finance.transactions.uncategorized") }}</span>
                                </p>
                                <p class="text-xs text-muted mt-0.5">{{ formatDateShort(tx.date) }} · {{ formatType(tx.type) }}</p>
                                <p v-if="tx.categoryName" class="text-xs text-muted">{{ tx.categoryName }}</p>
                                <div v-if="tx.tags?.length" class="flex flex-wrap gap-1 mt-1">
                                    <button
                                        v-for="tag in tx.tags"
                                        :key="tag"
                                        type="button"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-surface-2 text-secondary hover:bg-accent-500/15 hover:text-accent-400 transition-colors"
                                        :title="t('personal_finance.transactions.filter_by_tag_title', { tag })"
                                        v-on:click.stop="filterByTag(tag)"
                                    >
                                        #{{ tag }}
                                    </button>
                                </div>
                            </div>
                            <p class="font-mono text-sm shrink-0" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ formatAmount(tx) }}</p>
                        </div>
                        <slot name="extra-cells" :transaction="tx" />
                        <div class="flex items-center justify-end gap-0.5 pt-2 border-t border-line">
                            <AppIconButton v-if="!isSplitLeg(tx)" color="accent" :title="t('shared.common.edit')" v-on:click="onEditRow(tx)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="onDeleteRow(tx)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        </div>
                    </div>
                </div>

                <div v-if="items?.length" class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-2/50 border-b border-line/40">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.transactions.fields.date") }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.transactions.fields.type") }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.transactions.fields.amount") }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.transactions.fields.category") }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.transactions.fields.description") }}</th>
                                <slot name="extra-headers" />
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/40">
                            <tr v-for="tx in items" :key="tx.id" class="group hover:bg-surface-2/40 transition-colors">
                                <td class="px-6 py-3 text-xs whitespace-nowrap">{{ formatDateShort(tx.date) }}</td>
                                <td class="px-6 py-3">{{ formatType(tx.type) }}</td>
                                <td class="px-6 py-3 text-right font-mono" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ formatAmount(tx) }}</td>
                                <td class="px-6 py-3 text-muted">{{ tx.categoryName ?? t("personal_finance.transactions.uncategorized") }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <Paperclip v-if="tx.hasAttachment" class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                                        <ArrowRightLeft v-if="isTransferLeg(tx)" class="w-3.5 h-3.5 text-sky-400" :stroke-width="2" :title="t('personal_finance.transfers.leg_badge_title')" />
                                        <SplitIcon v-else-if="isSplitLeg(tx)" class="w-3.5 h-3.5 text-amber-400" :stroke-width="2" :title="t('personal_finance.splits.leg_badge_title')" />
                                        <Receipt v-else class="w-3.5 h-3.5 text-accent-400" :stroke-width="2" :title="t('personal_finance.transactions.leg_badge_title')" />
                                        <span>{{ tx.description }}</span>
                                    </div>
                                    <div v-if="tx.tags?.length" class="flex flex-wrap gap-1 mt-1">
                                        <button
                                            v-for="tag in tx.tags"
                                            :key="tag"
                                            type="button"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-surface-2 text-secondary hover:bg-accent-500/15 hover:text-accent-400 transition-colors"
                                            :title="t('personal_finance.transactions.filter_by_tag_title', { tag })"
                                            v-on:click.stop="filterByTag(tag)"
                                        >
                                            #{{ tag }}
                                        </button>
                                    </div>
                                </td>
                                <slot name="extra-cells" :transaction="tx" />
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <AppIconButton v-if="!isSplitLeg(tx)" color="accent" :title="t('shared.common.edit')" v-on:click="onEditRow(tx)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="onDeleteRow(tx)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:change="goToPage" />
                <AppLoader :active="loading" />
            </div>
        </template>

        <PersonalFinanceTransactionCreateModal
            ref="createModalRef"
            :wallets="wallets"
            :categories-by-wallet="categoriesByWallet"
            :types="types"
            :create-path="createTransactionPath"
            :extra-fields="extraFields"
            v-on:created="refreshAfterTx"
        >
            <template #extra-form-fields="slotProps">
                <slot name="extra-form-fields" v-bind="slotProps" />
            </template>
        </PersonalFinanceTransactionCreateModal>

        <PersonalFinanceTransactionEditModal
            ref="editModalRef"
            :categories-by-wallet="categoriesByWallet"
            :types="types"
            :update-path="updateTransactionPath"
            :upload-attachment-path="uploadAttachmentPath"
            :delete-attachment-path="deleteAttachmentPath"
            :serve-attachment-path="serveAttachmentPath"
            :extra-fields="extraFields"
            v-on:updated="refreshAfterTx"
            v-on:attachment-changed="refreshAfterTx"
        >
            <template #extra-form-fields="slotProps">
                <slot name="extra-form-fields" v-bind="slotProps" />
            </template>
        </PersonalFinanceTransactionEditModal>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">
                {{ t("personal_finance.transactions.delete_confirm", { name: describeTx(pendingDelete) }) }}
            </p>
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

        <AppModal
            :show="showTransfer"
            :title="transferEditing ? t('personal_finance.transfers.edit') : t('personal_finance.transfers.add')"
            :icon="ArrowRightLeft"
            :closeable="false"
            v-on:close="showTransfer = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitTransfer">
                <AppMultiselect
                    v-model="transferForm.fromWalletId"
                    :label="t('personal_finance.transfers.fields.from_wallet')"
                    :placeholder="t('personal_finance.transfers.placeholders.from_wallet')"
                    :options="walletOptions"
                    :allow-empty="false"
                    :disabled="transferEditing"
                    :error="transferErrors.fromWalletId"
                    required
                />
                <AppMultiselect
                    v-model="transferForm.toWalletId"
                    :label="t('personal_finance.transfers.fields.to_wallet')"
                    :placeholder="t('personal_finance.transfers.placeholders.to_wallet')"
                    :options="toWalletOptionsForTransfer"
                    :allow-empty="false"
                    :disabled="transferEditing"
                    :error="transferErrors.toWalletId"
                    required
                />
                <AppAmountInput
                    v-model="transferForm.amount"
                    :label="t('personal_finance.transfers.fields.amount')"
                    :placeholder="t('personal_finance.transactions.placeholders.amount')"
                    :error="transferErrors.amount"
                    required
                />
                <AppDatePicker
                    v-model="transferForm.date"
                    :label="t('personal_finance.transfers.fields.date')"
                    :placeholder="t('personal_finance.transactions.placeholders.date')"
                    :error="transferErrors.date"
                    required
                />
                <AppInput
                    v-model="transferForm.description"
                    :label="t('personal_finance.transfers.fields.description')"
                    :placeholder="t('personal_finance.transfers.placeholders.description')"
                    :error="transferErrors.description"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showTransfer = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="transferLoading"
                        v-on:click="submitTransfer"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!pendingTransferDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingTransferDelete = null"
        >
            <p class="text-sm text-primary">
                {{ t("personal_finance.transfers.delete_confirm") }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingTransferDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="transferDeleteLoading" v-on:click="doTransferDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="showSplit"
            :title="t('personal_finance.splits.add')"
            :icon="SplitIcon"
            :closeable="false"
            v-on:close="showSplit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitSplit">
                <AppMultiselect
                    v-model="splitForm.type"
                    :label="t('personal_finance.splits.fields.type')"
                    :placeholder="t('personal_finance.transactions.placeholders.type')"
                    :options="typeOptions"
                    :allow-empty="false"
                    required
                />
                <AppDatePicker
                    v-model="splitForm.date"
                    :label="t('personal_finance.splits.fields.date')"
                    :placeholder="t('personal_finance.transactions.placeholders.date')"
                    :error="splitErrors.date"
                    required
                />
                <AppInput
                    v-model="splitForm.description"
                    :label="t('personal_finance.splits.fields.description')"
                    :placeholder="t('personal_finance.transactions.placeholders.description')"
                />

                <div class="space-y-3">
                    <div
                        v-for="(part, idx) in splitForm.parts"
                        :key="idx"
                        class="border border-line rounded-md p-3 space-y-2 bg-surface-2/30"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium uppercase tracking-wider text-muted">#{{ idx + 1 }}</span>
                            <AppIconButton
                                v-if="splitForm.parts.length > 2"
                                color="rose"
                                :title="t('personal_finance.splits.remove_part')"
                                v-on:click="removeSplitPart(idx)"
                            >
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <AppMultiselect
                                v-model="part.categoryId"
                                :label="t('personal_finance.splits.fields.part_category')"
                                :placeholder="t('personal_finance.transactions.placeholders.category')"
                                :options="currentCategoryOptions"
                                :allow-empty="false"
                                required
                            />
                            <AppAmountInput
                                v-model="part.amount"
                                :label="t('personal_finance.splits.fields.part_amount')"
                                :placeholder="t('personal_finance.transactions.placeholders.amount')"
                                required
                            />
                        </div>
                        <AppInput
                            v-model="part.description"
                            :label="t('personal_finance.splits.fields.part_description')"
                            :placeholder="t('personal_finance.transactions.placeholders.description')"
                        />
                    </div>

                    <AppButton variant="ghost" size="sm" type="button" v-on:click="addSplitPart">
                        <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("personal_finance.splits.add_part") }}
                    </AppButton>

                    <p class="text-sm text-muted">
                        {{ t("personal_finance.splits.total", { total: splitTotal }) }}
                    </p>
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showSplit = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="splitLoading"
                        v-on:click="submitSplit"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!pendingSplitDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingSplitDelete = null"
        >
            <p class="text-sm text-primary">
                {{ t("personal_finance.splits.delete_confirm") }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingSplitDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="splitDeleteLoading" v-on:click="doSplitDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="showAdjust"
            :title="t('personal_finance.balance_adjustment.title')"
            :icon="Scale"
            :closeable="false"
            v-on:close="showAdjust = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitAdjust">
                <p class="text-sm text-muted">{{ t("personal_finance.balance_adjustment.hint") }}</p>
                <AppAmountInput
                    v-model="adjustForm.newBalance"
                    :label="t('personal_finance.balance_adjustment.fields.new_balance')"
                    :placeholder="t('personal_finance.balance_adjustment.placeholders.new_balance')"
                    :error="adjustErrors.newBalance"
                    required
                />
                <AppDatePicker
                    v-model="adjustForm.date"
                    :label="t('personal_finance.balance_adjustment.fields.date')"
                    :placeholder="t('personal_finance.transactions.placeholders.date')"
                    :error="adjustErrors.date"
                    required
                />
                <AppInput
                    v-model="adjustForm.description"
                    :label="t('personal_finance.balance_adjustment.fields.description')"
                    :placeholder="t('personal_finance.balance_adjustment.placeholders.description')"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showAdjust = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="adjustLoading"
                        v-on:click="submitAdjust"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("personal_finance.balance_adjustment.submit") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
