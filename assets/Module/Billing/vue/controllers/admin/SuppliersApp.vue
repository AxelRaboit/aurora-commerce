<script setup>
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { Eye, Trash2 } from "lucide-vue-next";

const { t } = useI18n();

const props = defineProps({
    suppliers: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    listPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    showPath: { type: String, required: true },
});

const { items, page, totalPages, search, onSearch, goToPage, reload } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.suppliers },
);

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reload(), 'admin.billing.suppliers.deleted',
);
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 max-w-md">
                <AppSearchInput v-model="search" :placeholder="t('admin.billing.suppliers.searchPlaceholder')" v-on:search="onSearch" />
            </div>
        </div>

        <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
            <AppNoData v-if="!items?.length" :message="t('admin.billing.suppliers.empty')" />
            <table v-else class="w-full text-sm">
                <thead class="bg-surface-2 text-xs text-secondary uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.billing.suppliers.name') }}</th>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.billing.suppliers.vatNumber') }}</th>
                        <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">{{ t('admin.billing.suppliers.registrationNumber') }}</th>
                        <th class="text-left px-4 py-3 font-semibold hidden lg:table-cell">{{ t('admin.billing.suppliers.iban') }}</th>
                        <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">{{ t('admin.billing.suppliers.email') }}</th>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.billing.suppliers.country') }}</th>
                        <th class="text-right px-4 py-3 font-semibold">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="supplier in items" :key="supplier.id" class="border-t border-line/60 hover:bg-surface-2/50 transition-colors">
                        <td class="px-4 py-3 text-primary font-medium truncate max-w-xs">{{ supplier.name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-secondary">{{ supplier.vatNumber ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-secondary hidden md:table-cell">{{ supplier.registrationNumber ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-secondary hidden lg:table-cell">{{ supplier.iban ?? '—' }}</td>
                        <td class="px-4 py-3 text-secondary hidden md:table-cell truncate max-w-xs">{{ supplier.email ?? '—' }}</td>
                        <td class="px-4 py-3 text-secondary">{{ supplier.countryCode ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="sky" :title="t('shared.common.view')" :href="buildPath(showPath, { id: supplier.id })">
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(supplier)">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t('admin.billing.suppliers.deleteConfirm', { name: pendingDelete?.name ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.billing.list.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>

    </div>
</template>
