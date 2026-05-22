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
    createCategoryPath: { type: String, required: true },
    updateCategoryPath: { type: String, required: true },
    deleteCategoryPath: { type: String, required: true },
});

const { t } = useI18n();

const selectedWalletId = ref(props.wallets[0]?.id ?? null);
const categoriesByWallet = ref({ ...props.categoriesByWallet });
const isSubmitting = ref(false);
const errors = ref({});
const newName = ref("");

const currentCategories = computed(() =>
    selectedWalletId.value
        ? categoriesByWallet.value[String(selectedWalletId.value)] ?? []
        : [],
);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deleteCategoryPath,
    (id) => {
        const key = String(selectedWalletId.value);
        categoriesByWallet.value[key] = (categoriesByWallet.value[key] ?? []).filter(
            (c) => c.id !== id,
        );
    },
    "personal_finance.categories.deleted",
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
    if (!selectedWalletId.value || !newName.value.trim()) return;
    isSubmitting.value = true;
    errors.value = {};
    try {
        const url = props.createCategoryPath.replace(
            "__walletId__",
            String(selectedWalletId.value),
        );
        const res = await request(HttpMethod.Post, url, { name: newName.value });
        if (!res.ok) {
            errors.value = res.payload?.errors ?? {};
            return;
        }
        const key = String(selectedWalletId.value);
        const list = categoriesByWallet.value[key] ?? [];
        categoriesByWallet.value[key] = [...list, res.payload.category];
        newName.value = "";
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <div class="p-6 space-y-6">
        <header class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ t("personal_finance.categories.title") }}</h1>
        </header>

        <section v-if="!wallets.length" class="text-muted text-sm">
            {{ t("personal_finance.categories.no_wallet") }}
        </section>

        <template v-else>
            <section class="bg-surface-2/30 border border-line rounded-lg p-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-col">
                        <label class="text-xs text-muted">{{ t("personal_finance.categories.wallet") }}</label>
                        <select v-model="selectedWalletId" class="bg-surface border border-line rounded px-2 py-1 text-sm">
                            <option v-for="w in wallets" :key="w.id" :value="w.id">{{ w.name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs text-muted">{{ t("personal_finance.categories.new_name") }}</label>
                        <input
                            v-model="newName"
                            type="text"
                            maxlength="120"
                            class="bg-surface border border-line rounded px-2 py-1 text-sm"
                            v-on:keyup.enter="onCreate"
                        >
                    </div>
                    <button
                        :disabled="isSubmitting || !newName.trim()"
                        class="bg-accent-600 hover:bg-accent-500 disabled:opacity-50 text-white text-sm px-3 py-1.5 rounded"
                        v-on:click="onCreate"
                    >
                        {{ t("personal_finance.categories.actions.create") }}
                    </button>
                </div>
                <p v-if="errors.name" class="text-xs text-rose-400 mt-2">{{ errors.name[0] }}</p>
            </section>

            <section>
                <ul v-if="currentCategories.length" class="space-y-1">
                    <li v-for="c in currentCategories" :key="c.id" class="flex items-center justify-between border-b border-line/40 py-2">
                        <span class="text-sm">{{ c.name }}</span>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(c)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </li>
                </ul>
                <p v-else class="text-muted text-sm">{{ t("personal_finance.categories.empty") }}</p>
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
                {{ t("personal_finance.categories.delete_confirm", { name: pendingDelete?.name ?? "" }) }}
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
