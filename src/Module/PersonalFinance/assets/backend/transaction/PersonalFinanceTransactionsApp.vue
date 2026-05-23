<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Pencil, Trash2, Save, X, Receipt, ArrowRightLeft, Split as SplitIcon, Paperclip, FileDown, Upload } from "lucide-vue-next";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
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
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useTransactionsCreate } from "./composables/useTransactionsCreate.js";
import { useTransactionsEdit } from "./composables/useTransactionsEdit.js";
import { useTransfersForm } from "./composables/useTransfersForm.js";
import { useTransfersDelete } from "./composables/useTransfersDelete.js";
import { useSplitsCreate } from "./composables/useSplitsCreate.js";
import { useSplitsDelete } from "./composables/useSplitsDelete.js";
import { useTransactionAttachment } from "./composables/useTransactionAttachment.js";

const props = defineProps({
    wallets: { type: Array, required: true },
    categoriesByWallet: { type: Object, required: true },
    selectedWalletId: { type: Number, default: null },
    transactions: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    listPath: { type: String, required: true },
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
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

const selectedWalletId = ref(props.selectedWalletId ?? props.wallets[0]?.id ?? null);

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.transactions,
        extraParams: () => ({ walletId: selectedWalletId.value }),
    },
);

watch(selectedWalletId, () => reset());

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

const { showCreate, createForm, createErrors, createLoading, openCreate, submitCreate } = useTransactionsCreate(
    props.createTransactionPath,
    () => reset(),
    { extraFields: props.extraFields },
);

const { showEdit, editingTransaction, editForm, editErrors, editLoading, openEdit, submitEdit } = useTransactionsEdit(
    props.updateTransactionPath,
    () => reset(),
    { extraFields: props.extraFields },
);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deleteTransactionPath,
    () => reset(),
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
    () => reset(),
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

const { loading: attachmentLoading, upload: uploadAttachment, remove: removeAttachment, serveUrl: attachmentServeUrl } =
    useTransactionAttachment(
        props.uploadAttachmentPath,
        props.deleteAttachmentPath,
        props.serveAttachmentPath,
        (updatedTransaction) => {
            if (updatedTransaction && editingTransaction.value && updatedTransaction.id === editingTransaction.value.id) {
                editingTransaction.value = updatedTransaction;
            }
            reset();
        },
    );

const attachmentInput = ref(null);

function onAttachmentPick(event) {
    const file = event.target.files?.[0];
    if (file && editingTransaction.value?.id) {
        uploadAttachment(editingTransaction.value.id, file);
    }
    event.target.value = "";
}

function onAttachmentRemove() {
    if (editingTransaction.value?.id) {
        removeAttachment(editingTransaction.value.id);
    }
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
    openEdit(tx);
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
    return `${tx.date} · ${formatType(tx.type)} ${formatAmount(tx)}`;
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
                <AppButton
                    variant="ghost"
                    size="md"
                    class="w-full sm:w-auto"
                    :disabled="!selectedWalletId || currentCategoryOptions.length <= 1"
                    v-on:click="openSplitCreate(selectedWalletId)"
                >
                    <SplitIcon class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.splits.add") }}
                </AppButton>
                <AppButton
                    variant="secondary"
                    size="md"
                    class="w-full sm:w-auto"
                    :disabled="wallets.length < 2"
                    v-on:click="openTransferCreate(selectedWalletId)"
                >
                    <ArrowRightLeft class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.transfers.add") }}
                </AppButton>
                <AppButton
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    :disabled="!selectedWalletId"
                    v-on:click="openCreate(selectedWalletId)"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.transactions.add") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <section v-if="!wallets.length" class="bg-surface border border-line rounded-lg p-6 text-muted text-sm">
            {{ t("personal_finance.transactions.no_wallet") }}
        </section>

        <template v-else>
            <div class="bg-surface border border-line rounded-lg p-4">
                <AppMultiselect
                    v-model="selectedWalletId"
                    :label="t('personal_finance.transactions.fields.wallet')"
                    :options="walletOptions"
                    :allow-empty="false"
                />
            </div>

            <div class="relative space-y-4">
                <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
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
                                <td class="px-6 py-3 font-mono text-xs">{{ tx.date }}</td>
                                <td class="px-6 py-3">{{ formatType(tx.type) }}</td>
                                <td class="px-6 py-3 text-right font-mono" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ formatAmount(tx) }}</td>
                                <td class="px-6 py-3 text-muted">{{ tx.categoryName ?? t("personal_finance.transactions.uncategorized") }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <Paperclip v-if="tx.hasAttachment" class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                                        <span>{{ tx.description }}</span>
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
                            <tr v-if="!items?.length">
                                <td :colspan="100" class="px-6 py-8 text-center text-sm text-muted">
                                    {{ t("personal_finance.transactions.empty") }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:change="goToPage" />
                <AppLoader :active="loading" />
            </div>
        </template>

        <AppModal
            :show="showCreate"
            :title="t('personal_finance.transactions.create_form_title')"
            :icon="Receipt"
            :closeable="false"
            v-on:close="showCreate = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppMultiselect
                    v-model="createForm.type"
                    :label="t('personal_finance.transactions.fields.type')"
                    :placeholder="t('personal_finance.transactions.placeholders.type')"
                    :options="typeOptions"
                    :allow-empty="false"
                    required
                />
                <AppAmountInput
                    v-model="createForm.amount"
                    :label="t('personal_finance.transactions.fields.amount')"
                    :placeholder="t('personal_finance.transactions.placeholders.amount')"
                    :error="createErrors.amount"
                    required
                />
                <AppDatePicker
                    v-model="createForm.date"
                    :label="t('personal_finance.transactions.fields.date')"
                    :placeholder="t('personal_finance.transactions.placeholders.date')"
                    :error="createErrors.date"
                    required
                />
                <AppMultiselect
                    v-model="createForm.categoryId"
                    :label="t('personal_finance.transactions.fields.category')"
                    :placeholder="t('personal_finance.transactions.placeholders.category')"
                    :options="currentCategoryOptions"
                    :allow-empty="true"
                />
                <AppInput
                    v-model="createForm.description"
                    :label="t('personal_finance.transactions.fields.description')"
                    :placeholder="t('personal_finance.transactions.placeholders.description')"
                    :error="createErrors.description"
                />
                <slot name="extra-form-fields" :form="createForm" :errors="createErrors" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="createLoading"
                        v-on:click="submitCreate"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="showEdit"
            :title="t('personal_finance.transactions.edit', { name: describeTx(editingTransaction) })"
            :icon="Pencil"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppMultiselect
                    v-model="editForm.type"
                    :label="t('personal_finance.transactions.fields.type')"
                    :placeholder="t('personal_finance.transactions.placeholders.type')"
                    :options="typeOptions"
                    :allow-empty="false"
                    required
                />
                <AppAmountInput
                    v-model="editForm.amount"
                    :label="t('personal_finance.transactions.fields.amount')"
                    :placeholder="t('personal_finance.transactions.placeholders.amount')"
                    :error="editErrors.amount"
                    required
                />
                <AppDatePicker
                    v-model="editForm.date"
                    :label="t('personal_finance.transactions.fields.date')"
                    :placeholder="t('personal_finance.transactions.placeholders.date')"
                    :error="editErrors.date"
                    required
                />
                <AppMultiselect
                    v-model="editForm.categoryId"
                    :label="t('personal_finance.transactions.fields.category')"
                    :placeholder="t('personal_finance.transactions.placeholders.category')"
                    :options="currentCategoryOptions"
                    :allow-empty="true"
                />
                <AppInput
                    v-model="editForm.description"
                    :label="t('personal_finance.transactions.fields.description')"
                    :placeholder="t('personal_finance.transactions.placeholders.description')"
                    :error="editErrors.description"
                />
                <slot name="extra-form-fields" :form="editForm" :errors="editErrors" :transaction="editingTransaction" />

                <div v-if="editingTransaction" class="border-t border-line pt-4 space-y-2">
                    <label class="text-xs font-medium uppercase tracking-wider text-muted">
                        {{ t("personal_finance.transactions.attachment.label") }}
                    </label>
                    <div v-if="editingTransaction.hasAttachment" class="flex items-center justify-between gap-3 bg-surface-2/40 border border-line rounded-md px-3 py-2">
                        <a
                            :href="attachmentServeUrl(editingTransaction.id)"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 text-sm text-accent hover:underline truncate"
                        >
                            <FileDown class="w-4 h-4" :stroke-width="2" />
                            <span class="truncate">{{ editingTransaction.attachmentOriginalName ?? t('personal_finance.transactions.attachment.download') }}</span>
                        </a>
                        <AppIconButton
                            color="rose"
                            :title="t('personal_finance.transactions.attachment.remove')"
                            :disabled="attachmentLoading"
                            v-on:click="onAttachmentRemove"
                        >
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                    <div v-else class="flex items-center gap-2">
                        <AppButton
                            variant="ghost"
                            size="sm"
                            type="button"
                            :loading="attachmentLoading"
                            v-on:click="attachmentInput?.click()"
                        >
                            <Upload class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("personal_finance.transactions.attachment.add") }}
                        </AppButton>
                        <span class="text-xs text-muted">{{ t("personal_finance.transactions.attachment.hint") }}</span>
                    </div>
                    <input
                        ref="attachmentInput"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,application/pdf"
                        class="hidden"
                        v-on:change="onAttachmentPick"
                    />
                </div>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        variant="primary"
                        size="md"
                        type="submit"
                        :loading="editLoading"
                        v-on:click="submitEdit"
                    >
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
    </div>
</template>
