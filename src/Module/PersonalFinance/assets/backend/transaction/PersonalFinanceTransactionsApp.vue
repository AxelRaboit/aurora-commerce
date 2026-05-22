<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { Trash2, X } from "lucide-vue-next";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";

const props = defineProps({
    wallets: { type: Array, required: true },
    categoriesByWallet: { type: Object, required: true },
    transactionsByWallet: { type: Object, required: true },
    types: { type: Array, required: true },
    createTransactionPath: { type: String, required: true },
    updateTransactionPath: { type: String, required: true },
    deleteTransactionPath: { type: String, required: true },
});

const { t } = useI18n();

const selectedWalletId = ref(props.wallets[0]?.id ?? null);
const transactionsByWallet = ref({ ...props.transactionsByWallet });
const isSubmitting = ref(false);
const errors = ref({});

const today = new Date().toISOString().slice(0, 10);
const form = ref({
    type: "expense",
    amount: "",
    date: today,
    description: "",
    categoryId: null,
});

const currentTransactions = computed(() =>
    selectedWalletId.value
        ? transactionsByWallet.value[String(selectedWalletId.value)] ?? []
        : [],
);

const currentCategories = computed(() =>
    selectedWalletId.value
        ? props.categoriesByWallet[String(selectedWalletId.value)] ?? []
        : [],
);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deleteTransactionPath,
    (id) => {
        const key = String(selectedWalletId.value);
        transactionsByWallet.value[key] = (transactionsByWallet.value[key] ?? []).filter(
            (tx) => tx.id !== id,
        );
    },
    "personal_finance.transactions.deleted",
);

async function request(method, url, body = null) {
    const options = { method, headers: { Accept: "application/json" } };
    if (body !== null) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(body);
    }
    const response = await fetch(url, options);
    const payload = await response.json().catch(() => ({}));
    return {
        ok: response.ok && payload.success !== false,
        status: response.status,
        payload,
    };
}

async function onCreate() {
    if (!selectedWalletId.value) return;
    isSubmitting.value = true;
    errors.value = {};
    try {
        const url = props.createTransactionPath.replace(
            "__walletId__",
            String(selectedWalletId.value),
        );
        const res = await request(HttpMethod.Post, url, {
            type: form.value.type,
            amount: form.value.amount,
            date: form.value.date,
            description: form.value.description || null,
            categoryId: form.value.categoryId || null,
        });
        if (!res.ok) {
            errors.value = res.payload?.errors ?? {};
            return;
        }
        const key = String(selectedWalletId.value);
        const list = transactionsByWallet.value[key] ?? [];
        transactionsByWallet.value[key] = [res.payload.transaction, ...list];
        form.value.amount = "";
        form.value.description = "";
    } finally {
        isSubmitting.value = false;
    }
}

function formatType(type) {
    return t(`personal_finance.transactions.types.${type}`);
}

function formatAmount(transaction) {
    const sign = transaction.type === "income" ? "+" : "-";
    return `${sign}${transaction.amount}`;
}

function describePending(tx) {
    if (!tx) return "";
    return `${tx.date} · ${formatType(tx.type)} ${formatAmount(tx)}`;
}
</script>

<template>
    <div class="p-6 space-y-6">
        <header class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ t("personal_finance.transactions.title") }}</h1>
        </header>

        <section v-if="!wallets.length" class="text-muted text-sm">
            {{ t("personal_finance.transactions.no_wallet") }}
        </section>

        <template v-else>
            <section class="bg-surface-2/30 border border-line rounded-lg p-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-col">
                        <label class="text-xs text-muted">{{ t("personal_finance.transactions.fields.wallet") }}</label>
                        <select v-model="selectedWalletId" class="bg-surface border border-line rounded px-2 py-1 text-sm">
                            <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs text-muted">{{ t("personal_finance.transactions.fields.type") }}</label>
                        <select v-model="form.type" class="bg-surface border border-line rounded px-2 py-1 text-sm">
                            <option v-for="ty in types" :key="ty" :value="ty">{{ formatType(ty) }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs text-muted">{{ t("personal_finance.transactions.fields.amount") }}</label>
                        <input
                            v-model="form.amount"
                            type="text"
                            inputmode="decimal"
                            pattern="\d{1,8}(\.\d{1,2})?"
                            class="bg-surface border border-line rounded px-2 py-1 text-sm w-28"
                        >
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs text-muted">{{ t("personal_finance.transactions.fields.date") }}</label>
                        <input v-model="form.date" type="date" class="bg-surface border border-line rounded px-2 py-1 text-sm">
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs text-muted">{{ t("personal_finance.transactions.fields.category") }}</label>
                        <select v-model="form.categoryId" class="bg-surface border border-line rounded px-2 py-1 text-sm">
                            <option :value="null">{{ t("personal_finance.transactions.uncategorized") }}</option>
                            <option v-for="c in currentCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col grow min-w-[12rem]">
                        <label class="text-xs text-muted">{{ t("personal_finance.transactions.fields.description") }}</label>
                        <input v-model="form.description" type="text" maxlength="255" class="bg-surface border border-line rounded px-2 py-1 text-sm">
                    </div>
                    <button :disabled="isSubmitting || !form.amount" class="bg-accent-600 hover:bg-accent-500 disabled:opacity-50 text-white text-sm px-3 py-1.5 rounded" v-on:click="onCreate">
                        {{ t("personal_finance.transactions.actions.create") }}
                    </button>
                </div>
                <p v-for="(msgs, field) in errors" :key="field" class="text-xs text-rose-400 mt-2">{{ field }}: {{ msgs[0] }}</p>
            </section>

            <section>
                <table v-if="currentTransactions.length" class="w-full text-sm">
                    <thead class="text-left text-muted border-b border-line">
                        <tr>
                            <th class="py-2">{{ t("personal_finance.transactions.fields.date") }}</th>
                            <th class="py-2">{{ t("personal_finance.transactions.fields.type") }}</th>
                            <th class="py-2">{{ t("personal_finance.transactions.fields.amount") }}</th>
                            <th class="py-2">{{ t("personal_finance.transactions.fields.category") }}</th>
                            <th class="py-2">{{ t("personal_finance.transactions.fields.description") }}</th>
                            <th class="py-2" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tx in currentTransactions" :key="tx.id" class="border-b border-line/40">
                            <td class="py-2 font-mono text-xs">{{ tx.date }}</td>
                            <td class="py-2">{{ formatType(tx.type) }}</td>
                            <td class="py-2 font-mono" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ formatAmount(tx) }}</td>
                            <td class="py-2 text-muted">{{ tx.categoryName ?? t("personal_finance.transactions.uncategorized") }}</td>
                            <td class="py-2">{{ tx.description }}</td>
                            <td class="py-2 text-right">
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(tx)">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-muted text-sm">{{ t("personal_finance.transactions.empty") }}</p>
            </section>
        </template>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">
                {{ t("personal_finance.transactions.delete_confirm", { name: describePending(pendingDelete) }) }}
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
