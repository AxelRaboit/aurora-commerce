<script setup>
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useDocumentCategoriesForm } from "./composables/useDocumentCategoriesForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { Plus, Pencil, Trash2, Save, X } from "lucide-vue-next";

const { t } = useI18n();
const { can } = usePrivileges();
const props = defineProps({
    categories: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
});

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath, { initialSearch: props.search, initialData: props.categories },
);

const {
    showCreate, newCategory, createErrors, createLoading, openCreate, submitCreate,
    showEdit, editingCategory, editForm, editErrors, editLoading, openEdit, submitEdit,
    pendingDelete, deleteLoading, confirmDelete, doDelete,
} = useDocumentCategoriesForm(props.createPath, props.updatePath, props.deletePath, reset);
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput v-model="searchInput" :placeholder="t('backend.ged.categories.searchPlaceholder')" v-on:search="onSearch" />
            <AppButton
                v-if="can('ged.documents.manage')"
                variant="primary"
                size="md"
                class="w-full sm:w-auto"
                v-on:click="openCreate"
            >
                <Plus class="w-4 h-4" :stroke-width="2" /> {{ t("backend.ged.categories.add") }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.ged.categories.name") }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("backend.ged.categories.slug") }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="cat in items" :key="cat.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3 font-medium text-primary">{{ cat.name }}</td>
                        <td class="px-6 py-3 text-muted font-mono text-xs hidden md:table-cell">{{ cat.slug }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="can('ged.documents.manage')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(cat)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('ged.documents.manage')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(cat)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td :colspan="3"><AppNoData :message="t('backend.ged.categories.empty')" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <AppModal :show="showCreate" :title="t('backend.ged.categories.create')" v-on:close="showCreate = false">
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="newCategory.name"
                    :label="t('backend.ged.categories.name')"
                    :placeholder="t('backend.ged.categories.namePlaceholder')"
                    :error="createErrors.name"
                    required
                />
                <AppInput v-model="newCategory.description" :label="t('backend.ged.categories.description')" :placeholder="t('backend.ged.categories.descriptionPlaceholder')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal :show="showEdit" :title="t('backend.ged.categories.edit', { name: editingCategory?.name ?? '' })" v-on:close="showEdit = false">
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('backend.ged.categories.name')"
                    :placeholder="t('backend.ged.categories.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <AppInput v-model="editForm.description" :label="t('backend.ged.categories.description')" :placeholder="t('backend.ged.categories.descriptionPlaceholder')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t("backend.ged.categories.deleteConfirm", { name: pendingDelete?.name ?? "" }) }}</p>
            <p class="text-sm text-secondary">{{ t("backend.ged.categories.deleteWarning") }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
