<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import { ShieldCheck, ShieldAlert, ShieldX, RefreshCw, ChevronDown, ChevronUp, ExternalLink } from "lucide-vue-next";

const { t } = useI18n();

const props = defineProps({
    reportPath: { type: String, required: true },
    invoicesPath: { type: String, required: true },
    showPath: { type: String, required: true },
});

const report = ref(null);
const loading = ref(false);
const error = ref(false);
const expanded = ref({ sequence: true, archive: false, audit: false });

const overallColor = computed(() => ({
    ok: 'emerald',
    warning: 'amber',
    error: 'rose',
}[report.value?.overall] ?? 'slate'));

const overallIcon = computed(() => ({
    ok: ShieldCheck,
    warning: ShieldAlert,
    error: ShieldX,
}[report.value?.overall] ?? ShieldCheck));

function statusColor(s) {
    return { ok: 'emerald', warning: 'amber', error: 'rose' }[s] ?? 'slate';
}

function statusLabel(s) {
    return t(`admin.billing.compliance.statusLabels.${s}`);
}

async function load() {
    loading.value = true;
    error.value = false;
    try {
        const res = await fetch(props.reportPath);
        const data = await res.json();
        if (data.success) {
            report.value = data;
            // Auto-expand sections with issues
            expanded.value.sequence = data.checks.sequence.status !== 'ok';
            expanded.value.archive = data.checks.archive.status !== 'ok';
            expanded.value.audit = data.checks.audit.status !== 'ok';
        } else {
            error.value = true;
        }
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
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
                <p class="font-semibold text-primary">{{ t('admin.billing.compliance.title') }}</p>
                <p v-if="loading" class="text-sm text-muted">{{ t('shared.common.loading') }}…</p>
                <p v-else-if="report" class="text-sm text-secondary">
                    {{ t('admin.billing.compliance.overall.' + report.overall) }}
                    <span class="text-muted ml-2 text-xs">{{ t('admin.billing.compliance.generatedAt', { date: new Date(report.generatedAt).toLocaleString() }) }}</span>
                </p>
                <p v-else-if="error" class="text-sm text-rose-400">{{ t('shared.common.error') }}</p>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 text-sm text-secondary hover:text-primary transition-colors"
                :class="{ 'animate-spin': loading }"
                v-on:click="load"
            >
                <RefreshCw class="w-4 h-4" :stroke-width="2" />
            </button>
        </div>

        <template v-if="report">
            <!-- Sequence integrity -->
            <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                <button
                    type="button"
                    class="w-full px-6 py-4 flex items-center gap-3 hover:bg-surface-2/40 transition-colors"
                    v-on:click="expanded.sequence = !expanded.sequence"
                >
                    <AppBadge :color="statusColor(report.checks.sequence.status)">{{ statusLabel(report.checks.sequence.status) }}</AppBadge>
                    <span class="font-semibold text-primary flex-1 text-left">{{ t('admin.billing.compliance.checks.sequence.title') }}</span>
                    <span class="text-sm text-muted">
                        {{ report.checks.sequence.gapCount === 0
                            ? t('admin.billing.compliance.checks.sequence.noGaps')
                            : t('admin.billing.compliance.checks.sequence.gaps', { n: report.checks.sequence.gapCount }) }}
                    </span>
                    <component :is="expanded.sequence ? ChevronUp : ChevronDown" class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
                </button>

                <div v-if="expanded.sequence" class="border-t border-line/40 px-6 py-4 space-y-4">
                    <p class="text-sm text-secondary">{{ t('admin.billing.compliance.checks.sequence.description') }}</p>
                    <div v-if="!report.checks.sequence.years.length" class="text-sm text-muted italic">
                        {{ t('admin.billing.compliance.checks.sequence.noNumberedInvoices') }}
                    </div>
                    <div v-for="year in report.checks.sequence.years" :key="year.year" class="rounded-lg border border-line/40 overflow-hidden">
                        <div class="flex items-center gap-3 px-4 py-2 bg-surface-2/50">
                            <AppBadge :color="statusColor(year.status)" size="sm">{{ year.status === 'ok' ? t('admin.billing.compliance.status.ok') : year.gaps.length + ' ' + t('admin.billing.compliance.checks.sequence.gapUnit') }}</AppBadge>
                            <span class="font-medium text-primary text-sm">{{ t('admin.billing.compliance.checks.sequence.year', { year: year.year }) }}</span>
                            <span class="text-xs text-muted ml-auto">{{ t('admin.billing.compliance.checks.sequence.invoiceCount', { n: year.total }) }}</span>
                        </div>
                        <div v-if="year.gaps.length" class="px-4 py-3">
                            <p class="text-xs text-muted mb-2">{{ t('admin.billing.compliance.checks.sequence.missingNumbers') }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                <code v-for="gap in year.gaps" :key="gap" class="text-xs bg-rose-500/10 text-rose-400 px-2 py-0.5 rounded font-mono">{{ gap }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archive alerts -->
            <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                <button
                    type="button"
                    class="w-full px-6 py-4 flex items-center gap-3 hover:bg-surface-2/40 transition-colors"
                    v-on:click="expanded.archive = !expanded.archive"
                >
                    <AppBadge :color="statusColor(report.checks.archive.status)">{{ statusLabel(report.checks.archive.status) }}</AppBadge>
                    <span class="font-semibold text-primary flex-1 text-left">{{ t('admin.billing.compliance.checks.archive.title') }}</span>
                    <span class="text-sm text-muted">
                        {{ report.checks.archive.count === 0
                            ? t('admin.billing.compliance.checks.archive.ok')
                            : t('admin.billing.compliance.checks.archive.overdue', { n: report.checks.archive.count, years: report.checks.archive.thresholdYears }) }}
                    </span>
                    <component :is="expanded.archive ? ChevronUp : ChevronDown" class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
                </button>

                <div v-if="expanded.archive" class="border-t border-line/40">
                    <p class="text-sm text-secondary px-6 py-4">{{ t('admin.billing.compliance.checks.archive.description', { years: report.checks.archive.thresholdYears }) }}</p>
                    <div v-if="report.checks.archive.invoices.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-line/40">
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.invoices.number') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.invoices.issuedAt') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.invoices.statusLabel') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted" />
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/40">
                                <tr v-for="inv in report.checks.archive.invoices" :key="inv.id" class="hover:bg-surface-2/40 transition-colors">
                                    <td class="px-6 py-3 font-mono text-xs text-primary">{{ inv.number ?? '#' + inv.id }}</td>
                                    <td class="px-6 py-3 text-secondary text-xs">{{ inv.issuedAt }}</td>
                                    <td class="px-6 py-3"><AppBadge color="amber">{{ inv.status }}</AppBadge></td>
                                    <td class="px-6 py-3 text-right">
                                        <a :href="buildPath(showPath, { id: inv.id })" class="inline-flex items-center gap-1 text-xs text-accent-400 hover:text-accent-300">
                                            {{ t('shared.common.view') }} <ExternalLink class="w-3 h-3" :stroke-width="2" />
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="px-6 pb-4 text-sm text-emerald-400">{{ t('admin.billing.compliance.checks.archive.ok') }}</p>
                </div>
            </div>

            <!-- Audit trail -->
            <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
                <button
                    type="button"
                    class="w-full px-6 py-4 flex items-center gap-3 hover:bg-surface-2/40 transition-colors"
                    v-on:click="expanded.audit = !expanded.audit"
                >
                    <AppBadge :color="statusColor(report.checks.audit.status)">{{ statusLabel(report.checks.audit.status) }}</AppBadge>
                    <span class="font-semibold text-primary flex-1 text-left">{{ t('admin.billing.compliance.checks.audit.title') }}</span>
                    <span class="text-sm text-muted">
                        {{ report.checks.audit.anomalyCount === 0
                            ? t('admin.billing.compliance.checks.audit.ok')
                            : t('admin.billing.compliance.checks.audit.anomalies', { n: report.checks.audit.anomalyCount }) }}
                    </span>
                    <component :is="expanded.audit ? ChevronUp : ChevronDown" class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
                </button>

                <div v-if="expanded.audit" class="border-t border-line/40">
                    <p class="text-sm text-secondary px-6 py-4">{{ t('admin.billing.compliance.checks.audit.description') }}</p>
                    <div v-if="report.checks.audit.anomalies.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-surface-2/50 border-b border-line/40">
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.compliance.checks.audit.action') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.compliance.checks.audit.entity') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.compliance.checks.audit.date') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.billing.compliance.checks.audit.user') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/40">
                                <tr v-for="anomaly in report.checks.audit.anomalies" :key="anomaly.id" class="hover:bg-surface-2/40 transition-colors">
                                    <td class="px-6 py-3 font-mono text-xs text-amber-400">{{ anomaly.action }}</td>
                                    <td class="px-6 py-3 text-xs text-secondary">#{{ anomaly.entityId }}</td>
                                    <td class="px-6 py-3 text-xs text-muted">{{ new Date(anomaly.createdAt).toLocaleString() }}</td>
                                    <td class="px-6 py-3 text-xs text-rose-400 italic">{{ t('admin.billing.compliance.checks.audit.noUser') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="px-6 pb-4 text-sm text-emerald-400">{{ t('admin.billing.compliance.checks.audit.ok') }}</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-surface border border-line/60 rounded-xl p-4 text-center">
                    <p class="text-2xl font-semibold text-primary tabular-nums">{{ report.stats.totalIssued }}</p>
                    <p class="text-xs text-muted mt-1">{{ t('admin.billing.compliance.stats.totalIssued') }}</p>
                </div>
                <div v-for="(count, status) in report.stats.byStatus" :key="status" class="bg-surface border border-line/60 rounded-xl p-4 text-center">
                    <p class="text-2xl font-semibold text-primary tabular-nums">{{ count }}</p>
                    <p class="text-xs text-muted mt-1">{{ t('admin.billing.invoices.status.' + status) }}</p>
                </div>
            </div>
        </template>
    </div>
</template>
