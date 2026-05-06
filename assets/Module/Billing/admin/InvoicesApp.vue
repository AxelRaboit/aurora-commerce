<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { Plus, Eye, Trash2, Download, Calendar, TrendingUp, AlertCircle, FileText } from "lucide-vue-next";
import { formatCents } from "@/shared/utils/format/formatPrice.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    invoices: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    listPath: { type: String, required: true },
    showPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    exportXlsxPath: { type: String, required: true },
    importPath: { type: String, required: true },
    statusOptions: { type: Array, default: () => [] },
});

const statusFilter = ref("");
const counts = ref(props.invoices.counts ?? {});

const { items, page, totalPages, search, onSearch, goToPage, reload } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.invoices,
        extraParams: () => ({ status: statusFilter.value || undefined }),
        onData: (data) => { counts.value = data.counts ?? {}; },
    },
);

function onStatusChange() {
    reload();
}

function goToInvoice(id) {
    window.location.href = buildPath(props.showPath, { id });
}

const exportXlsxUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    const qs = params.toString();
    return qs ? `${props.exportXlsxPath}?${qs}` : props.exportXlsxPath;
});



const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reload(), 'admin.billing.invoices.deleted',
);

const STATUS_SELECT = computed(() =>
    props.statusOptions.map(option => ({
        value: option.value,
        label: `${t(option.labelKey)} (${counts.value[option.value] ?? 0})`,
    })),
);

const { formatDateNumeric } = useDateFormat();
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-surface border border-line/60 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.dashboard.stats.thisMonth') }}</p>
                    <Calendar class="w-4 h-4 text-muted" :stroke-width="2" />
                </div>
                <p class="text-xl font-semibold text-primary tabular-nums">{{ formatCents(stats.monthGrossCents) }}</p>
            </div>
            <div class="bg-surface border border-line/60 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.dashboard.stats.thisYear') }}</p>
                    <TrendingUp class="w-4 h-4 text-muted" :stroke-width="2" />
                </div>
                <p class="text-xl font-semibold text-primary tabular-nums">{{ formatCents(stats.yearGrossCents) }}</p>
            </div>
            <div class="bg-surface border border-line/60 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.dashboard.stats.needsReview') }}</p>
                    <AlertCircle class="w-4 h-4 text-muted" :stroke-width="2" />
                </div>
                <p class="text-xl font-semibold text-primary tabular-nums">{{ stats.needsReviewCount }}</p>
            </div>
            <div class="bg-surface border border-line/60 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.dashboard.stats.totalInvoices') }}</p>
                    <FileText class="w-4 h-4 text-muted" :stroke-width="2" />
                </div>
                <p class="text-xl font-semibold text-primary tabular-nums">{{ stats.totalInvoices }}</p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
            <div class="flex-1">
                <AppSearchInput v-model="search" :placeholder="t('admin.billing.invoices.searchPlaceholder')" v-on:search="onSearch" />
            </div>
            <AppMultiselect
                v-model="statusFilter"
                :options="STATUS_SELECT"
                :placeholder="t('admin.billing.list.allStatuses')"
                :allow-empty="true"
                class="sm:max-w-xs"
                v-on:update:model-value="onStatusChange"
            />
            <div class="flex items-center gap-2">
                <AppButton variant="secondary" size="md" :href="exportXlsxUrl">
                    <Download class="w-4 h-4" :stroke-width="2" />
                    {{ t('admin.billing.invoices.exportXlsx') }}
                </AppButton>
                <AppButton v-if="can('billing.invoices.edit')" variant="primary" size="md" :href="importPath">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('admin.billing.invoices.importOcr') }}
                </AppButton>
            </div>
        </div>

        <div class="bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <AppNoData v-if="!items?.length" :message="t('admin.billing.invoices.empty')" />
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.invoices.number') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.invoices.supplier') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.billing.invoices.issuedAt') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('admin.billing.invoices.dueAt') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.invoices.totalGross') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.invoices.statusLabel') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="invoice in items" :key="invoice.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3 font-mono text-xs text-primary">{{ invoice.number ?? '—' }}</td>
                        <td class="px-6 py-3 text-primary font-medium truncate max-w-xs">{{ invoice.supplier?.name ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ formatDateNumeric(invoice.issuedAt) }}</td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">{{ formatDateNumeric(invoice.dueAt) }}</td>
                        <td class="px-6 py-3 text-primary text-right tabular-nums">
                            {{ formatCents(invoice.totalGrossCents, invoice.currency) }}
                        </td>
                        <td class="px-6 py-3">
                            <AppBadge :color="invoice.statusColor">{{ t(`admin.billing.invoices.status.${invoice.status}`) }}</AppBadge>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="sky" :title="t('shared.common.view')" v-on:click="goToInvoice(invoice.id)">
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="invoice.isDeletable && can('billing.invoices.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(invoice)">
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
            <p class="text-sm text-primary">{{ t('admin.billing.invoices.deleteConfirm', { number: pendingDelete?.number ?? ('#' + (pendingDelete?.id ?? '')) }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.billing.list.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
