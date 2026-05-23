<script setup>
import { ref, defineExpose, defineEmits, watch } from "vue";
import { useI18n } from "vue-i18n";
import { List, Pencil, Trash2, X, Receipt, ArrowRightLeft, Split as SplitIcon } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import PersonalFinanceTransactionEditModal from "../../transaction/components/PersonalFinanceTransactionEditModal.vue";
import { useBudgetItemTransactions } from "../composables/useBudgetItemTransactions.js";

const props = defineProps({
    /** { [walletId]: Category[] } — forwarded to the nested edit modal. */
    categoriesByWallet: { type: Object, required: true },
    /** Available transaction types. */
    types: { type: Array, required: true },
    /** GET `__id__` path that returns { transactions: [...] } for a budget item. */
    itemTransactionsPath: { type: String, required: true },
    /** Update path with `__id__` placeholder. */
    updateTransactionPath: { type: String, required: true },
    /** Delete path with `__id__` placeholder. */
    deleteTransactionPath: { type: String, required: true },
    /** Attachment paths — forwarded to the nested edit modal. */
    uploadAttachmentPath: { type: String, required: true },
    deleteAttachmentPath: { type: String, required: true },
    serveAttachmentPath: { type: String, required: true },
    /** Client-extension hook — forwarded to the nested edit modal. */
    extraFields: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["changed"]);

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const editModalRef = ref(null);

const {
    show,
    currentItem,
    transactions,
    loading,
    pendingDelete,
    deleteLoading,
    open,
    close,
    applyUpdated,
    confirmDelete,
    doDelete,
} = useBudgetItemTransactions(props.itemTransactionsPath, props.deleteTransactionPath);

function isTransferLeg(tx) {
    return !!tx?.transferId;
}

function isSplitLeg(tx) {
    return !!tx?.splitId;
}

function signedAmount(tx) {
    const sign = tx.type === "income" ? "+" : "-";
    return `${sign}${tx.amount}`;
}

function onEdit(tx) {
    if (isSplitLeg(tx)) return;
    editModalRef.value?.open(tx);
}

async function onConfirmDelete() {
    const ok = await doDelete();
    if (ok) emit("changed");
}

function onEditUpdated(updated) {
    applyUpdated(updated);
    emit("changed");
}

// When the user closes the modal after any mutation, propagate one
// final `changed` so the budget refreshes balances + diff just in
// case a stale state was missed during in-modal mutations.
watch(show, (visible, wasVisible) => {
    if (wasVisible && !visible) emit("changed");
});

defineExpose({ open });
</script>

<template>
    <AppModal
        :show="show"
        max-width="2xl"
        :title="t('personal_finance.budget.transactions_modal_title', { name: currentItem?.label ?? '' })"
        :icon="List"
        :closeable="false"
        v-on:close="close"
    >
        <div class="relative space-y-3">
            <p v-if="!transactions.length && !loading" class="bg-surface-2/40 border border-line rounded-md px-3 py-4 text-sm text-muted text-center">
                {{ t("personal_finance.budget.transactions_modal_empty") }}
            </p>

            <ul v-if="transactions.length" class="divide-y divide-line/40 border border-line rounded-md overflow-hidden">
                <li v-for="tx in transactions" :key="tx.id" class="px-3 py-2 flex items-center gap-3 bg-surface">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-primary truncate flex items-center gap-1.5">
                            <ArrowRightLeft v-if="isTransferLeg(tx)" class="w-3.5 h-3.5 text-sky-400 shrink-0" :stroke-width="2" :title="t('personal_finance.transfers.leg_badge_title')" />
                            <SplitIcon v-else-if="isSplitLeg(tx)" class="w-3.5 h-3.5 text-amber-400 shrink-0" :stroke-width="2" :title="t('personal_finance.splits.leg_badge_title')" />
                            <Receipt v-else class="w-3.5 h-3.5 text-accent-400 shrink-0" :stroke-width="2" :title="t('personal_finance.transactions.leg_badge_title')" />
                            <span class="truncate">{{ tx.description ?? t("personal_finance.transactions.uncategorized") }}</span>
                        </p>
                        <p class="text-xs text-muted">{{ formatDateShort(tx.date) }}</p>
                    </div>
                    <span class="font-mono text-sm shrink-0" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ signedAmount(tx) }}</span>
                    <div class="flex items-center gap-0.5 shrink-0">
                        <AppIconButton v-if="!isSplitLeg(tx)" color="accent" :title="t('shared.common.edit')" v-on:click="onEdit(tx)">
                            <Pencil class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(tx)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </li>
            </ul>

            <AppLoader :active="loading" />
        </div>

        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="close">
                    <X class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.close") }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>

    <PersonalFinanceTransactionEditModal
        ref="editModalRef"
        :categories-by-wallet="categoriesByWallet"
        :types="types"
        :update-path="updateTransactionPath"
        :upload-attachment-path="uploadAttachmentPath"
        :delete-attachment-path="deleteAttachmentPath"
        :serve-attachment-path="serveAttachmentPath"
        :extra-fields="extraFields"
        v-on:updated="onEditUpdated"
        v-on:attachment-changed="onEditUpdated"
    />

    <AppModal
        :show="!!pendingDelete"
        max-width="sm"
        :closeable="false"
        :title="t('shared.common.delete')"
        :icon="Trash2"
        v-on:close="pendingDelete = null"
    >
        <p class="text-sm text-primary">{{ t("personal_finance.transactions.delete_confirm", { name: pendingDelete ? `${pendingDelete.date} · ${signedAmount(pendingDelete)}` : '' }) }}</p>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">
                    <X class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.cancel") }}
                </AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="onConfirmDelete">
                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.delete") }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
