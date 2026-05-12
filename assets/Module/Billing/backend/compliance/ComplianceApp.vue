<script setup>
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import { RefreshCw, ChevronDown, ChevronUp, ExternalLink, Hash, Archive, ClipboardList } from "lucide-vue-next";
import { useComplianceReport } from "./composables/useComplianceReport.js";

const { t } = useI18n();

const props = defineProps({
    reportPath: { type: String, required: true },
    invoicesPath: { type: String, required: true },
    showPath: { type: String, required: true },
});

const {
    report,
    loading,
    error,
    expanded,
    overallColor,
    overallIcon,
    statusColor,
    statusLabel,
    load,
} = useComplianceReport(props.reportPath);
</script>

<template>
    <div class="space-y-6">
        <!-- Header summary -->
        <div class="bg-surface border border-line/60 rounded-xl p-6 flex items-center gap-4">
            <div v-if="loading" class="w-10 h-10 rounded-full bg-surface-2 animate-pulse" />
            <component
                :is="overallIcon"
                v-else-if="report"
                class="w-10 h-10 shrink-0"
                :class="`text-${overallColor}-400`"
                :stroke-width="1.5"
            />
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-primary">{{ t('backend.billing.compliance.title') }}</p>
                <p v-if="loading" class="text-sm text-muted">{{ t('shared.common.loading') }}</p>
                <p v-else-if="report" class="text-sm text-secondary">
                    {{ t('backend.billing.compliance.overall.' + report.overall) }}
                    <span class="text-muted ml-2 text-xs">{{ t('backend.billing.compliance.generatedAt', { date: new Date(report.generatedAt).toLocaleString() }) }}</span>
                </p>
                <p v-else-if="error" class="text-sm text-rose-400">{{ t('shared.common.error') }}</p>
            </div>
            <button
                type="button"
                class="p-2 rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors"
                :class="{ 'animate-spin': loading }"
                v-on:click="load"
            >
                <RefreshCw class="w-4 h-4" :stroke-width="2" />
            </button>
        </div>

        <!-- Skeleton -->
        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div v-for="index in 3" :key="index" class="bg-surface border border-line/60 rounded-xl p-5 space-y-4 animate-pulse">
                <div class="flex items-center justify-between">
                    <div class="w-8 h-8 rounded-lg bg-surface-2" />
                    <div class="w-14 h-5 rounded-full bg-surface-2" />
                </div>
                <div class="w-10 h-8 rounded bg-surface-2" />
                <div class="w-24 h-3 rounded bg-surface-2" />
                <div class="w-32 h-4 rounded bg-surface-2" />
            </div>
        </div>

        <template v-if="report">
            <!-- Check cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Sequence -->
                <div
                    class="bg-surface rounded-xl border overflow-hidden"
                    :class="report.checks.sequence.status === 'ok' ? 'border-line/60' : `border-${statusColor(report.checks.sequence.status)}-500/40`"
                >
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-2 rounded-lg" :class="`bg-${statusColor(report.checks.sequence.status)}-500/10`">
                                <Hash class="w-4 h-4" :class="`text-${statusColor(report.checks.sequence.status)}-400`" :stroke-width="2" />
                            </div>
                            <AppBadge :color="statusColor(report.checks.sequence.status)" size="sm">{{ statusLabel(report.checks.sequence.status) }}</AppBadge>
                        </div>
                        <p
                            class="text-3xl font-semibold tabular-nums"
                            :class="report.checks.sequence.gapCount > 0 ? `text-${statusColor(report.checks.sequence.status)}-400` : 'text-primary'"
                        >
                            {{ report.checks.sequence.gapCount }}
                        </p>
                        <p class="text-xs text-muted mt-0.5">{{ t('backend.billing.compliance.checks.sequence.gapUnit') }}</p>
                        <p class="text-sm font-medium text-primary mt-3">{{ t('backend.billing.compliance.checks.sequence.title') }}</p>
                    </div>
                    <button
                        type="button"
                        class="w-full px-5 py-2.5 border-t border-line/40 flex items-center justify-between text-xs text-muted hover:text-primary hover:bg-surface-2/40 transition-colors"
                        v-on:click="expanded.sequence = !expanded.sequence"
                    >
                        <span>{{ expanded.sequence ? t('shared.common.collapse') : t('shared.common.expand') }}</span>
                        <component :is="expanded.sequence ? ChevronUp : ChevronDown" class="w-3.5 h-3.5" :stroke-width="2" />
                    </button>
                </div>

                <!-- Archive -->
                <div
                    class="bg-surface rounded-xl border overflow-hidden"
                    :class="report.checks.archive.status === 'ok' ? 'border-line/60' : `border-${statusColor(report.checks.archive.status)}-500/40`"
                >
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-2 rounded-lg" :class="`bg-${statusColor(report.checks.archive.status)}-500/10`">
                                <Archive class="w-4 h-4" :class="`text-${statusColor(report.checks.archive.status)}-400`" :stroke-width="2" />
                            </div>
                            <AppBadge :color="statusColor(report.checks.archive.status)" size="sm">{{ statusLabel(report.checks.archive.status) }}</AppBadge>
                        </div>
                        <p
                            class="text-3xl font-semibold tabular-nums"
                            :class="report.checks.archive.count > 0 ? `text-${statusColor(report.checks.archive.status)}-400` : 'text-primary'"
                        >
                            {{ report.checks.archive.count }}
                        </p>
                        <p class="text-xs text-muted mt-0.5">
                            {{ report.checks.archive.count === 0
                                ? t('backend.billing.compliance.checks.archive.ok')
                                : t('backend.billing.compliance.checks.archive.overdue', { n: report.checks.archive.count, years: report.checks.archive.thresholdYears }) }}
                        </p>
                        <p class="text-sm font-medium text-primary mt-3">{{ t('backend.billing.compliance.checks.archive.title') }}</p>
                    </div>
                    <button
                        type="button"
                        class="w-full px-5 py-2.5 border-t border-line/40 flex items-center justify-between text-xs text-muted hover:text-primary hover:bg-surface-2/40 transition-colors"
                        v-on:click="expanded.archive = !expanded.archive"
                    >
                        <span>{{ expanded.archive ? t('shared.common.collapse') : t('shared.common.expand') }}</span>
                        <component :is="expanded.archive ? ChevronUp : ChevronDown" class="w-3.5 h-3.5" :stroke-width="2" />
                    </button>
                </div>

                <!-- Audit -->
                <div
                    class="bg-surface rounded-xl border overflow-hidden"
                    :class="report.checks.audit.status === 'ok' ? 'border-line/60' : `border-${statusColor(report.checks.audit.status)}-500/40`"
                >
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div class="p-2 rounded-lg" :class="`bg-${statusColor(report.checks.audit.status)}-500/10`">
                                <ClipboardList class="w-4 h-4" :class="`text-${statusColor(report.checks.audit.status)}-400`" :stroke-width="2" />
                            </div>
                            <AppBadge :color="statusColor(report.checks.audit.status)" size="sm">{{ statusLabel(report.checks.audit.status) }}</AppBadge>
                        </div>
                        <p
                            class="text-3xl font-semibold tabular-nums"
                            :class="report.checks.audit.anomalyCount > 0 ? `text-${statusColor(report.checks.audit.status)}-400` : 'text-primary'"
                        >
                            {{ report.checks.audit.anomalyCount }}
                        </p>
                        <p class="text-xs text-muted mt-0.5">
                            {{ report.checks.audit.anomalyCount === 0
                                ? t('backend.billing.compliance.checks.audit.ok')
                                : t('backend.billing.compliance.checks.audit.anomalies', { n: report.checks.audit.anomalyCount }) }}
                        </p>
                        <p class="text-sm font-medium text-primary mt-3">{{ t('backend.billing.compliance.checks.audit.title') }}</p>
                    </div>
                    <button
                        type="button"
                        class="w-full px-5 py-2.5 border-t border-line/40 flex items-center justify-between text-xs text-muted hover:text-primary hover:bg-surface-2/40 transition-colors"
                        v-on:click="expanded.audit = !expanded.audit"
                    >
                        <span>{{ expanded.audit ? t('shared.common.collapse') : t('shared.common.expand') }}</span>
                        <component :is="expanded.audit ? ChevronUp : ChevronDown" class="w-3.5 h-3.5" :stroke-width="2" />
                    </button>
                </div>
            </div>

            <!-- Sequence details -->
            <div v-if="expanded.sequence" class="bg-surface border border-line/60 rounded-xl px-6 py-4 space-y-4">
                <p class="text-sm text-secondary">{{ t('backend.billing.compliance.checks.sequence.description') }}</p>
                <div v-if="!report.checks.sequence.years.length" class="text-sm text-muted italic">
                    {{ t('backend.billing.compliance.checks.sequence.noNumberedInvoices') }}
                </div>
                <div v-for="year in report.checks.sequence.years" :key="year.year" class="rounded-lg border border-line/40 overflow-hidden">
                    <div class="flex items-center gap-3 px-4 py-2 bg-surface-2/50">
                        <AppBadge :color="statusColor(year.status)" size="sm">{{ year.status === 'ok' ? t('backend.billing.compliance.status.ok') : year.gaps.length + ' ' + t('backend.billing.compliance.checks.sequence.gapUnit') }}</AppBadge>
                        <span class="font-medium text-primary text-sm">{{ t('backend.billing.compliance.checks.sequence.year', { year: year.year }) }}</span>
                        <span class="text-xs text-muted ml-auto">{{ t('backend.billing.compliance.checks.sequence.invoiceCount', { n: year.total }) }}</span>
                    </div>
                    <div v-if="year.gaps.length" class="px-4 py-3">
                        <p class="text-xs text-muted mb-2">{{ t('backend.billing.compliance.checks.sequence.missingNumbers') }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            <code v-for="gap in year.gaps" :key="gap" class="text-xs bg-rose-500/10 text-rose-400 px-2 py-0.5 rounded font-mono">{{ gap }}</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archive details -->
            <div v-if="expanded.archive" class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                <p class="text-sm text-secondary px-6 py-4">{{ t('backend.billing.compliance.checks.archive.description', { years: report.checks.archive.thresholdYears }) }}</p>
                <div v-if="report.checks.archive.invoices.length" class="overflow-x-auto border-t border-line/40">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-2/50 border-b border-line/40">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.invoices.number') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.invoices.issuedAt') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.invoices.statusLabel') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/40">
                            <tr v-for="inv in report.checks.archive.invoices" :key="inv.id" class="hover:bg-surface-2/40 transition-colors">
                                <td class="px-6 py-3 font-mono text-xs text-primary">{{ inv.number ?? '#' + inv.id }}</td>
                                <td class="px-6 py-3 text-secondary text-xs">{{ inv.issuedAt }}</td>
                                <td class="px-6 py-3"><AppBadge color="amber">{{ t(`backend.billing.invoices.status.${inv.status}`) }}</AppBadge></td>
                                <td class="px-6 py-3 text-right">
                                    <a :href="buildPath(showPath, { id: inv.id })" class="inline-flex items-center gap-1 text-xs text-accent-400 hover:text-accent-300">
                                        {{ t('shared.common.view') }} <ExternalLink class="w-3 h-3" :stroke-width="2" />
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Audit details -->
            <div v-if="expanded.audit" class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                <p class="text-sm text-secondary px-6 py-4">{{ t('backend.billing.compliance.checks.audit.description') }}</p>
                <div v-if="report.checks.audit.anomalies.length" class="overflow-x-auto border-t border-line/40">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-2/50 border-b border-line/40">
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.compliance.checks.audit.action') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.compliance.checks.audit.entity') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.compliance.checks.audit.date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.billing.compliance.checks.audit.user') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/40">
                            <tr v-for="anomaly in report.checks.audit.anomalies" :key="anomaly.id" class="hover:bg-surface-2/40 transition-colors">
                                <td class="px-6 py-3 font-mono text-xs text-amber-400">{{ anomaly.action }}</td>
                                <td class="px-6 py-3 text-xs text-secondary">#{{ anomaly.entityId }}</td>
                                <td class="px-6 py-3 text-xs text-muted">{{ new Date(anomaly.createdAt).toLocaleString() }}</td>
                                <td class="px-6 py-3 text-xs text-rose-400 italic">{{ t('backend.billing.compliance.checks.audit.noUser') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-surface border border-line/60 rounded-xl p-5">
                    <p class="text-xs text-muted mb-2">{{ t('backend.billing.compliance.stats.totalIssued') }}</p>
                    <p class="text-2xl font-semibold text-primary tabular-nums">{{ report.stats.totalIssued }}</p>
                </div>
                <div v-for="(count, status) in report.stats.byStatus" :key="status" class="bg-surface border border-line/60 rounded-xl p-5">
                    <p class="text-xs text-muted mb-2">{{ t('backend.billing.invoices.status.' + status) }}</p>
                    <p class="text-2xl font-semibold text-primary tabular-nums">{{ count }}</p>
                </div>
            </div>
        </template>
    </div>
</template>
