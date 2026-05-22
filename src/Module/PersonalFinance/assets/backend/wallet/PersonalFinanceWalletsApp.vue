<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Trash2, X } from "lucide-vue-next";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { usePersonalFinanceWalletsApi } from "./composables/usePersonalFinanceWalletsApi.js";

const props = defineProps({
    wallets: { type: Array, required: true },
    modes: { type: Array, required: true },
    createWalletPath: { type: String, required: true },
    updateWalletPath: { type: String, required: true },
    deleteWalletPath: { type: String, required: true },
});

const { t } = useI18n();

const { wallets, isSubmitting, errors, createWallet } =
    usePersonalFinanceWalletsApi(props.wallets, {
        createWalletPath: props.createWalletPath,
        updateWalletPath: props.updateWalletPath,
        deleteWalletPath: props.deleteWalletPath,
    });

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deleteWalletPath,
    (id) => {
        wallets.value = wallets.value.filter((w) => w.id !== id);
    },
    "personal_finance.wallets.deleted",
);

const form = ref({
    name: "",
    startBalance: "0.00",
    mode: "simple",
});

async function onSubmit() {
    try {
        await createWallet({ ...form.value });
        form.value = { name: "", startBalance: "0.00", mode: "simple" };
    } catch {
        // errors surfaced via composable
    }
}

function formatMode(mode) {
    return t(`personal_finance.wallets.modes.${mode}`);
}
</script>

<template>
    <div class="p-6 space-y-6">
        <header class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">
                {{ t("personal_finance.wallets.title") }}
            </h1>
        </header>

        <section class="bg-surface-2/30 border border-line rounded-lg p-4">
            <h2 class="text-sm font-medium text-muted mb-3">
                {{ t("personal_finance.wallets.create_form_title") }}
            </h2>
            <form class="flex flex-wrap items-end gap-3" v-on:submit.prevent="onSubmit">
                <div class="flex flex-col">
                    <label class="text-xs text-muted">{{ t("personal_finance.wallets.fields.name") }}</label>
                    <input
                        v-model="form.name"
                        type="text"
                        maxlength="120"
                        class="bg-surface border border-line rounded px-2 py-1 text-sm"
                        :placeholder="t('personal_finance.wallets.placeholders.name')"
                        required
                    >
                </div>
                <div class="flex flex-col">
                    <label class="text-xs text-muted">{{ t("personal_finance.wallets.fields.start_balance") }}</label>
                    <input
                        v-model="form.startBalance"
                        type="text"
                        inputmode="decimal"
                        pattern="-?\d{1,8}(\.\d{1,2})?"
                        class="bg-surface border border-line rounded px-2 py-1 text-sm w-28"
                    >
                </div>
                <div class="flex flex-col">
                    <label class="text-xs text-muted">{{ t("personal_finance.wallets.fields.mode") }}</label>
                    <select v-model="form.mode" class="bg-surface border border-line rounded px-2 py-1 text-sm">
                        <option v-for="m in modes" :key="m" :value="m">{{ formatMode(m) }}</option>
                    </select>
                </div>
                <button
                    type="submit"
                    :disabled="isSubmitting"
                    class="bg-accent-600 hover:bg-accent-500 disabled:opacity-50 text-white text-sm px-3 py-1.5 rounded"
                >
                    {{ t("personal_finance.wallets.actions.create") }}
                </button>
            </form>
            <p v-if="errors.name" class="text-xs text-rose-400 mt-2">{{ errors.name[0] }}</p>
            <p v-if="errors.startBalance" class="text-xs text-rose-400 mt-2">{{ errors.startBalance[0] }}</p>
        </section>

        <section>
            <table v-if="wallets.length" class="w-full text-sm">
                <thead class="text-left text-muted border-b border-line">
                    <tr>
                        <th class="py-2">{{ t("personal_finance.wallets.fields.name") }}</th>
                        <th class="py-2">{{ t("personal_finance.wallets.fields.mode") }}</th>
                        <th class="py-2">{{ t("personal_finance.wallets.fields.start_balance") }}</th>
                        <th class="py-2" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="w in wallets" :key="w.id" class="border-b border-line/40">
                        <td class="py-2">{{ w.name }}</td>
                        <td class="py-2">{{ formatMode(w.mode) }}</td>
                        <td class="py-2 font-mono">{{ w.startBalance }}</td>
                        <td class="py-2 text-right">
                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(w)">
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="text-muted text-sm">{{ t("personal_finance.wallets.empty") }}</p>
        </section>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">
                {{ t("personal_finance.wallets.delete_confirm", { name: pendingDelete?.name ?? "" }) }}
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
