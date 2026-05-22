<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Pencil, Trash2, Save, X, Wallet } from "lucide-vue-next";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppAmountInput from "@/shared/components/form/input/AppAmountInput.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useWalletsCreate } from "./composables/useWalletsCreate.js";
import { useWalletsEdit } from "./composables/useWalletsEdit.js";

const props = defineProps({
    wallets: { type: Object, default: () => ({}) },
    modes: { type: Array, required: true },
    search: { type: String, default: "" },
    listPath: { type: String, required: true },
    createWalletPath: { type: String, required: true },
    updateWalletPath: { type: String, required: true },
    deleteWalletPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.wallets },
);

const modeOptions = computed(() =>
    props.modes.map((m) => ({ value: m, label: t(`personal_finance.wallets.modes.${m}`) })),
);

function formatMode(mode) {
    return t(`personal_finance.wallets.modes.${mode}`);
}

const { showCreate, createForm, createErrors, createLoading, openCreate, submitCreate } = useWalletsCreate(
    props.createWalletPath,
    () => reset(),
    { extraFields: props.extraFields },
);

const { showEdit, editingWallet, editForm, editErrors, editLoading, openEdit, submitEdit } = useWalletsEdit(
    props.updateWalletPath,
    () => reset(),
    { extraFields: props.extraFields },
);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deleteWalletPath,
    () => reset(),
    "personal_finance.wallets.deleted",
);
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('personal_finance.wallets.search_placeholder')"
                v-on:search="onSearch"
            />
            <template #actions>
                <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openCreate">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.wallets.add") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <div class="relative space-y-4">
            <div class="sm:hidden space-y-3">
                <div v-for="w in items" :key="w.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary">{{ w.name }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ formatMode(w.mode) }}</p>
                        </div>
                        <p class="text-sm font-mono shrink-0">{{ w.startBalance }}</p>
                    </div>
                    <slot name="extra-cells" :wallet="w" />
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
                            <slot name="extra-headers" />
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="w in items" :key="w.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3"><span class="font-medium text-primary">{{ w.name }}</span></td>
                            <td class="px-6 py-3 text-secondary">{{ formatMode(w.mode) }}</td>
                            <td class="px-6 py-3 text-right font-mono text-primary">{{ w.startBalance }}</td>
                            <slot name="extra-cells" :wallet="w" />
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(w)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(w)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items?.length">
                            <td :colspan="100" class="px-6 py-8 text-center text-sm text-muted">
                                {{ t("personal_finance.wallets.empty") }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:change="goToPage" />
            <AppLoader :active="loading" />
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
                <AppAmountInput
                    v-model="createForm.startBalance"
                    :label="t('personal_finance.wallets.fields.start_balance')"
                    :placeholder="t('personal_finance.wallets.placeholders.start_balance')"
                    :error="createErrors.startBalance"
                />
                <AppMultiselect
                    v-model="createForm.mode"
                    :label="t('personal_finance.wallets.fields.mode')"
                    :options="modeOptions"
                    :allow-empty="false"
                    required
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
                <AppAmountInput
                    v-model="editForm.startBalance"
                    :label="t('personal_finance.wallets.fields.start_balance')"
                    :placeholder="t('personal_finance.wallets.placeholders.start_balance')"
                    :error="editErrors.startBalance"
                />
                <AppMultiselect
                    v-model="editForm.mode"
                    :label="t('personal_finance.wallets.fields.mode')"
                    :options="modeOptions"
                    :allow-empty="false"
                    required
                />
                <slot name="extra-form-fields" :form="editForm" :errors="editErrors" :wallet="editingWallet" />
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
