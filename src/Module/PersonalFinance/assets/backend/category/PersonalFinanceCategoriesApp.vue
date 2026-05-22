<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Pencil, Trash2, Save, X, Tags } from "lucide-vue-next";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppSearchInput from "@/shared/components/form/input/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useCategoriesCreate } from "./composables/useCategoriesCreate.js";
import { useCategoriesEdit } from "./composables/useCategoriesEdit.js";

const props = defineProps({
    wallets: { type: Array, required: true },
    selectedWalletId: { type: Number, default: null },
    categories: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    listPath: { type: String, required: true },
    createCategoryPath: { type: String, required: true },
    updateCategoryPath: { type: String, required: true },
    deleteCategoryPath: { type: String, required: true },
    /** Client-extension hook — cf. `entity_extensibility_convention.md` §"Couche 5". */
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();

const selectedWalletId = ref(props.selectedWalletId ?? props.wallets[0]?.id ?? null);

const { items, loading, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.categories,
        extraParams: () => ({ walletId: selectedWalletId.value }),
    },
);

watch(selectedWalletId, () => reset());

const walletOptions = computed(() =>
    props.wallets.map((w) => ({ value: w.id, label: w.name })),
);

const { showCreate, createForm, createErrors, createLoading, openCreate, submitCreate } = useCategoriesCreate(
    props.createCategoryPath,
    () => reset(),
    { extraFields: props.extraFields },
);

const { showEdit, editingCategory, editForm, editErrors, editLoading, openEdit, submitEdit } = useCategoriesEdit(
    props.updateCategoryPath,
    () => reset(),
    { extraFields: props.extraFields },
);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deleteCategoryPath,
    () => reset(),
    "personal_finance.categories.deleted",
);
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('personal_finance.categories.search_placeholder')"
                v-on:search="onSearch"
            />
            <template #actions>
                <AppButton
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    :disabled="!selectedWalletId"
                    v-on:click="openCreate(selectedWalletId)"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("personal_finance.categories.add") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <section v-if="!wallets.length" class="bg-surface border border-line rounded-lg p-6 text-muted text-sm">
            {{ t("personal_finance.categories.no_wallet") }}
        </section>

        <template v-else>
            <div class="bg-surface border border-line rounded-lg p-4">
                <AppMultiselect
                    v-model="selectedWalletId"
                    :label="t('personal_finance.categories.wallet')"
                    :options="walletOptions"
                    :allow-empty="false"
                />
            </div>

            <div class="relative space-y-4">
                <div class="bg-surface border border-line rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-2/50 border-b border-line/40">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.categories.fields.name") }}</th>
                                <slot name="extra-headers" />
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/40">
                            <tr v-for="c in items" :key="c.id" class="group hover:bg-surface-2/40 transition-colors">
                                <td class="px-6 py-3"><span class="font-medium text-primary">{{ c.name }}</span></td>
                                <slot name="extra-cells" :category="c" />
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(c)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(c)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!items?.length">
                                <td :colspan="100" class="px-6 py-8 text-center text-sm text-muted">
                                    {{ t("personal_finance.categories.empty") }}
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
            :title="t('personal_finance.categories.create_form_title')"
            :icon="Tags"
            :closeable="false"
            v-on:close="showCreate = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="createForm.name"
                    :label="t('personal_finance.categories.fields.name')"
                    :placeholder="t('personal_finance.categories.placeholders.name')"
                    :error="createErrors.name"
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
            :title="t('personal_finance.categories.edit', { name: editingCategory?.name ?? '' })"
            :icon="Pencil"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('personal_finance.categories.fields.name')"
                    :error="editErrors.name"
                    required
                />
                <slot name="extra-form-fields" :form="editForm" :errors="editErrors" :category="editingCategory" />
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
