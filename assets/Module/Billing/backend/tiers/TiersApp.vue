<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { Eye, Trash2, X } from "lucide-vue-next";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    tiersData: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    typeFilter: { type: String, default: "" },
    typeOptions: { type: Array, default: () => [] },
    listPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    showPath: { type: String, required: true },
});

const activeType = ref(props.typeFilter);

const TYPE_SELECT = [
    { value: "", label: t("backend.billing.tiers.allTypes") },
    ...props.typeOptions.map(o => ({ value: o.value, label: t(o.labelKey) })),
];

const { items, page, totalPages, search, onSearch, goToPage, reload } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.tiersData,
        extraParams: () => ({ type: activeType.value || undefined }),
    },
);

function onTypeChange() { reload(); }

const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reload(), 'backend.billing.tiers.deleted',
);

const TYPE_BADGE = {
    supplier: 'sky',
    client: 'emerald',
    partner: 'violet',
    subcontractor: 'amber',
    other: 'slate',
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            <div class="flex-1">
                <AppSearchInput v-model="search" :placeholder="t('backend.billing.tiers.searchPlaceholder')" v-on:search="onSearch" />
            </div>
            <AppMultiselect
                v-model="activeType"
                :options="TYPE_SELECT"
                :allow-empty="false"
                class="sm:max-w-xs"
                v-on:update:model-value="onTypeChange"
            />
        </div>

        <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <AppNoData v-if="!items?.length" :message="t('backend.billing.tiers.empty')" />
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.tiers.type.label') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.suppliers.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.suppliers.vatNumber') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.billing.suppliers.email') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.suppliers.country') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="tiers in items" :key="tiers.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3">
                            <AppBadge :color="TYPE_BADGE[tiers.type] ?? 'slate'">{{ t(`backend.billing.tiers.type.${tiers.type}`) }}</AppBadge>
                        </td>
                        <td class="px-6 py-3 text-primary font-medium truncate max-w-xs">{{ tiers.name }}</td>
                        <td class="px-6 py-3 font-mono text-xs text-secondary">{{ tiers.vatNumber ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell truncate max-w-xs">{{ tiers.email ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary">{{ tiers.countryCode ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="sky" :title="t('shared.common.view')" :href="buildPath(showPath, { id: tiers.id })">
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="can('billing.tiers.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(tiers)">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t('backend.billing.tiers.deleteConfirm', { name: pendingDelete?.name ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.billing.list.deleteWarning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
