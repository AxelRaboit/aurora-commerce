<script setup>
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { Plus, FileText, AlertCircle, TrendingUp, Calendar, Eye } from "lucide-vue-next";
import { formatCents } from "@/shared/utils/format/formatPrice.js";

const { t } = useI18n();

const props = defineProps({
    stats: { type: Object, required: true },
    countsByStatus: { type: Object, default: () => ({}) },
    topSuppliers: { type: Array, default: () => [] },
    needsReview: { type: Array, default: () => [] },
    invoicesPath: { type: String, required: true },
    showPath: { type: String, required: true },
    importPath: { type: String, required: true },
    statusOptions: { type: Array, default: () => [] },
});

function statusFilterUrl(status) {
    return `${props.invoicesPath}?status=${status}`;
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-end gap-2">
            <AppButton variant="primary" size="md" :href="importPath">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.billing.invoices.importOcr') }}
            </AppButton>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-surface border border-line/60 rounded-xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.dashboard.stats.thisMonth') }}</p>
                    <Calendar class="w-4 h-4 text-muted" :stroke-width="2" />
                </div>
                <p class="text-2xl font-semibold text-primary tabular-nums">{{ formatCents(stats.monthGrossCents) }}</p>
            </div>
            <div class="bg-surface border border-line/60 rounded-xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.dashboard.stats.thisYear') }}</p>
                    <TrendingUp class="w-4 h-4 text-muted" :stroke-width="2" />
                </div>
                <p class="text-2xl font-semibold text-primary tabular-nums">{{ formatCents(stats.yearGrossCents) }}</p>
            </div>
            <a :href="statusFilterUrl('needs_review')" class="bg-surface border border-line/60 rounded-xl p-5 hover:bg-surface-2/50 transition-colors block">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.dashboard.stats.needsReview') }}</p>
                    <AlertCircle class="w-4 h-4 text-amber-400" :stroke-width="2" />
                </div>
                <p class="text-2xl font-semibold tabular-nums" :class="stats.needsReviewCount > 0 ? 'text-amber-400' : 'text-primary'">{{ stats.needsReviewCount }}</p>
            </a>
            <a :href="invoicesPath" class="bg-surface border border-line/60 rounded-xl p-5 hover:bg-surface-2/50 transition-colors block">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted uppercase tracking-wide">{{ t('admin.billing.dashboard.stats.totalInvoices') }}</p>
                    <FileText class="w-4 h-4 text-muted" :stroke-width="2" />
                </div>
                <p class="text-2xl font-semibold text-primary tabular-nums">{{ stats.totalInvoices }}</p>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-surface border border-line/60 rounded-xl p-6 lg:col-span-1">
                <h3 class="font-semibold text-primary mb-4">{{ t('admin.billing.dashboard.statusBreakdown') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li v-for="option in statusOptions" :key="option.value" class="flex items-center justify-between">
                        <AppBadge :color="option.color" :href="statusFilterUrl(option.value)">{{ t(option.labelKey) }}</AppBadge>
                        <span class="text-secondary tabular-nums">{{ countsByStatus[option.value] ?? 0 }}</span>
                    </li>
                </ul>
            </div>

            <div class="bg-surface border border-line/60 rounded-xl p-6 lg:col-span-2">
                <h3 class="font-semibold text-primary mb-4">{{ t('admin.billing.dashboard.topSuppliers') }}</h3>
                <AppNoData v-if="!topSuppliers.length" :message="t('admin.billing.dashboard.noTopSuppliers')" />
                <ul v-else class="divide-y divide-line/60">
                    <li v-for="(supplier, index) in topSuppliers" :key="supplier.supplierId" class="flex items-center justify-between py-2.5 text-sm">
                        <span class="flex items-center gap-3">
                            <span class="text-xs text-muted tabular-nums w-5">{{ index + 1 }}.</span>
                            <span class="text-primary font-medium">{{ supplier.supplierName }}</span>
                        </span>
                        <span class="text-primary tabular-nums">{{ formatCents(supplier.total) }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-line/60 flex items-center justify-between">
                <h3 class="font-semibold text-primary">{{ t('admin.billing.dashboard.needsReviewQueue') }}</h3>
                <a :href="statusFilterUrl('needs_review')" class="text-sm text-accent-400 hover:text-accent-300">{{ t('shared.common.view') }} →</a>
            </div>
            <AppNoData v-if="!needsReview.length" :message="t('admin.billing.dashboard.allClear')" />
            <table v-else class="w-full text-sm">
                <thead class="bg-surface-2 text-xs text-secondary uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.billing.invoices.number') }}</th>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.billing.invoices.supplier') }}</th>
                        <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">{{ t('admin.billing.invoices.issuedAt') }}</th>
                        <th class="text-right px-4 py-3 font-semibold">{{ t('admin.billing.invoices.totalGross') }}</th>
                        <th class="text-right px-4 py-3 font-semibold">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="invoice in needsReview" :key="invoice.id" class="border-t border-line/60 hover:bg-surface-2/50 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs text-primary">{{ invoice.number ?? '—' }}</td>
                        <td class="px-4 py-3 text-primary font-medium truncate max-w-xs">{{ invoice.supplier?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-secondary hidden md:table-cell">{{ invoice.issuedAt ?? '—' }}</td>
                        <td class="px-4 py-3 text-primary text-right tabular-nums">{{ formatCents(invoice.totalGrossCents) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a :href="buildPath(showPath, { id: invoice.id })" class="inline-flex items-center gap-1 text-accent-400 hover:text-accent-300 text-sm">
                                <Eye class="w-4 h-4" :stroke-width="2" />
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
