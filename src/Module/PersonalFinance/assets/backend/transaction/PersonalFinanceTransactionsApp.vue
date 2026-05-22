<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Pencil, Trash2, Save, X, Receipt } from "lucide-vue-next";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useTransactionsCreate } from "./composables/useTransactionsCreate.js";
import { useTransactionsEdit } from "./composables/useTransactionsEdit.js";

const props = defineProps({
    wallets: { type: Array, required: true },
    categoriesByWallet: { type: Object, required: true },
    transactionsByWallet: { type: Object, required: true },
    types: { type: Array, required: true },
    createTransactionPath: { type: String, required: true },
    updateTransactionPath: { type: String, required: true },
    deleteTransactionPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

const selectedWalletId = ref(props.wallets[0]?.id ?? null);
const transactionsByWallet = ref({ ...props.transactionsByWallet });
const searchInput = ref("");

const walletOptions = computed(() =>
    props.wallets.map((w) => ({ value: w.id, label: w.name })),
);

const typeOptions = computed(() =>
    props.types.map((ty) => ({ value: ty, label: t(`personal_finance.transactions.types.${ty}`) })),
);

const currentTransactions = computed(() => {
    if (!selectedWalletId.value) return [];
    const list = transactionsByWallet.value[String(selectedWalletId.value)] ?? [];
    const q = searchInput.value.trim().toLowerCase();
    if (!q) return list;
    return list.filter((tx) =>
        (tx.description ?? "").toLowerCase().includes(q)
        || (tx.categoryName ?? "").toLowerCase().includes(q),
    );
});

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
    (created, walletId) => {
        const key = String(walletId);
        transactionsByWallet.value[key] = [created, ...(transactionsByWallet.value[key] ?? [])];
    },
    { extraFields: props.extraFields },
);

const { showEdit, editingTransaction, editForm, editErrors, editLoading, openEdit, submitEdit } = useTransactionsEdit(
    props.updateTransactionPath,
    (updated) => {
        const key = String(updated.walletId);
        const list = transactionsByWallet.value[key] ?? [];
        const idx = list.findIndex((tx) => tx.id === updated.id);
        if (idx !== -1) transactionsByWallet.value[key] = [...list.slice(0, idx), updated, ...list.slice(idx + 1)];
    },
    { extraFields: props.extraFields },
);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deleteTransactionPath,
    (id) => {
        const key = String(selectedWalletId.value);
        transactionsByWallet.value[key] = (transactionsByWallet.value[key] ?? []).filter((tx) => tx.id !== id);
    },
    "personal_finance.transactions.deleted",
);

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
            <AppSearchInput v-model="searchInput" :placeholder="t('personal_finance.transactions.search_placeholder')" />
            <template #actions>
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
                        <tr v-for="tx in currentTransactions" :key="tx.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3 font-mono text-xs">{{ tx.date }}</td>
                            <td class="px-6 py-3">{{ formatType(tx.type) }}</td>
                            <td class="px-6 py-3 text-right font-mono" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ formatAmount(tx) }}</td>
                            <td class="px-6 py-3 text-muted">{{ tx.categoryName ?? t("personal_finance.transactions.uncategorized") }}</td>
                            <td class="px-6 py-3">{{ tx.description }}</td>
                            <slot name="extra-cells" :transaction="tx" />
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(tx)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(tx)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!currentTransactions.length">
                            <td :colspan="100" class="px-6 py-8 text-center text-sm text-muted">
                                {{ t("personal_finance.transactions.empty") }}
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                <AppInput
                    v-model="createForm.date"
                    :label="t('personal_finance.transactions.fields.date')"
                    type="date"
                    :error="createErrors.date"
                    required
                />
                <AppMultiselect
                    v-model="createForm.categoryId"
                    :label="t('personal_finance.transactions.fields.category')"
                    :options="currentCategoryOptions"
                    :allow-empty="true"
                />
                <AppInput
                    v-model="createForm.description"
                    :label="t('personal_finance.transactions.fields.description')"
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
                    :options="typeOptions"
                    :allow-empty="false"
                    required
                />
                <AppAmountInput
                    v-model="editForm.amount"
                    :label="t('personal_finance.transactions.fields.amount')"
                    :error="editErrors.amount"
                    required
                />
                <AppInput
                    v-model="editForm.date"
                    :label="t('personal_finance.transactions.fields.date')"
                    type="date"
                    :error="editErrors.date"
                    required
                />
                <AppMultiselect
                    v-model="editForm.categoryId"
                    :label="t('personal_finance.transactions.fields.category')"
                    :options="currentCategoryOptions"
                    :allow-empty="true"
                />
                <AppInput
                    v-model="editForm.description"
                    :label="t('personal_finance.transactions.fields.description')"
                    :error="editErrors.description"
                />
                <slot name="extra-form-fields" :form="editForm" :errors="editErrors" :transaction="editingTransaction" />
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
    </div>
</template>
