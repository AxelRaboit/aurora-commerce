<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Pencil, Trash2, Save, X, Wallet } from "lucide-vue-next";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppSelect from "@/shared/components/form/select/AppSelect.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";

const props = defineProps({
    wallets: { type: Array, required: true },
    modes: { type: Array, required: true },
    createWalletPath: { type: String, required: true },
    updateWalletPath: { type: String, required: true },
    deleteWalletPath: { type: String, required: true },
});

const { t } = useI18n();

const wallets = ref([...props.wallets]);

const modeOptions = computed(() =>
    props.modes.map((m) => ({ value: m, label: t(`personal_finance.wallets.modes.${m}`) })),
);

function emptyForm() {
    return { name: "", startBalance: "0.00", mode: "simple", showOnDashboard: true, position: 0 };
}

const showCreate = ref(false);
const createForm = ref(emptyForm());
const createErrors = ref({});
const createLoading = ref(false);

const showEdit = ref(false);
const editingWallet = ref(null);
const editForm = ref(emptyForm());
const editErrors = ref({});
const editLoading = ref(false);

async function request(method, url, body = null) {
    const options = { method, headers: { Accept: "application/json" } };
    if (body !== null) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(body);
    }
    const response = await fetch(url, options);
    const payload = await response.json().catch(() => ({}));
    return { ok: response.ok && payload.success !== false, payload };
}

function openCreate() {
    createForm.value = emptyForm();
    createErrors.value = {};
    showCreate.value = true;
}

async function submitCreate() {
    createLoading.value = true;
    createErrors.value = {};
    try {
        const res = await request(HttpMethod.Post, props.createWalletPath, createForm.value);
        if (!res.ok) {
            createErrors.value = res.payload?.errors ?? {};
            return;
        }
        wallets.value = [...wallets.value, res.payload.wallet];
        showCreate.value = false;
    } finally {
        createLoading.value = false;
    }
}

function openEdit(wallet) {
    editingWallet.value = wallet;
    editForm.value = {
        name: wallet.name,
        startBalance: wallet.startBalance,
        mode: wallet.mode,
        showOnDashboard: wallet.showOnDashboard ?? true,
        position: wallet.position ?? 0,
    };
    editErrors.value = {};
    showEdit.value = true;
}

async function submitEdit() {
    if (!editingWallet.value) return;
    editLoading.value = true;
    editErrors.value = {};
    try {
        const url = buildPath(props.updateWalletPath, { id: editingWallet.value.id });
        const res = await request(HttpMethod.Post, url, editForm.value);
        if (!res.ok) {
            editErrors.value = res.payload?.errors ?? {};
            return;
        }
        const idx = wallets.value.findIndex((w) => w.id === editingWallet.value.id);
        if (idx !== -1) wallets.value[idx] = res.payload.wallet;
        showEdit.value = false;
    } finally {
        editLoading.value = false;
    }
}

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deleteWalletPath,
    (id) => {
        wallets.value = wallets.value.filter((w) => w.id !== id);
    },
    "personal_finance.wallets.deleted",
);

function formatMode(mode) {
    return t(`personal_finance.wallets.modes.${mode}`);
}
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <template #actions>
                <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openCreate">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.wallets.add") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div class="space-y-4">
            <div class="sm:hidden space-y-3">
                <div v-for="w in wallets" :key="w.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary">{{ w.name }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ formatMode(w.mode) }}</p>
                        </div>
                        <p class="text-sm font-mono shrink-0">{{ w.startBalance }}</p>
                    </div>
                    <div class="flex items-center justify-end gap-0.5 pt-2 border-t border-line">
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(w)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(w)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.wallets.fields.name") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.wallets.fields.mode") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.wallets.fields.start_balance") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="w in wallets" :key="w.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3">
                                <span class="font-medium text-primary">{{ w.name }}</span>
                            </td>
                            <td class="px-6 py-3 text-secondary">{{ formatMode(w.mode) }}</td>
                            <td class="px-6 py-3 text-right font-mono text-primary">{{ w.startBalance }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(w)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(w)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!wallets.length">
                            <td :colspan="4" class="px-6 py-8 text-center text-sm text-muted">{{ t("personal_finance.wallets.empty") }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <AppModal
            :show="showCreate"
            :title="t('personal_finance.wallets.create_form_title')"
            :icon="Wallet"
            :closeable="false"
            v-on:close="showCreate = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="createForm.name"
                    :label="t('personal_finance.wallets.fields.name')"
                    :placeholder="t('personal_finance.wallets.placeholders.name')"
                    :error="createErrors.name"
                    required
                />
                <AppInput
                    v-model="createForm.startBalance"
                    :label="t('personal_finance.wallets.fields.start_balance')"
                    placeholder="0.00"
                    :error="createErrors.startBalance"
                />
                <AppSelect
                    v-model="createForm.mode"
                    :label="t('personal_finance.wallets.fields.mode')"
                    :options="modeOptions"
                    required
                />
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
            :title="t('personal_finance.wallets.edit', { name: editingWallet?.name ?? '' })"
            :icon="Pencil"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('personal_finance.wallets.fields.name')"
                    :error="editErrors.name"
                    required
                />
                <AppInput
                    v-model="editForm.startBalance"
                    :label="t('personal_finance.wallets.fields.start_balance')"
                    :error="editErrors.startBalance"
                />
                <AppSelect
                    v-model="editForm.mode"
                    :label="t('personal_finance.wallets.fields.mode')"
                    :options="modeOptions"
                    required
                />
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
