<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { RefreshCw, Wallet, TrendingUp, TrendingDown, Scale, Globe2, Target, Repeat, Clock, AlertTriangle } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useOverviewData } from "./composables/useOverviewData.js";
import { buildSparklinePath, deltaClass, formatDelta, signedAmount } from "./composables/overviewFormatters.js";

const props = defineProps({
    snapshot: { type: Object, required: true },
    refreshPath: { type: String, required: true },
    walletTransactionsPath: { type: String, required: true },
});

const { t } = useI18n();
const { formatDateShort, formatMonthYear } = useDateFormat();
const { snapshot: snap, loading, refresh } = useOverviewData(props.refreshPath, props.snapshot);

const monthLabel = computed(() => formatMonthYear(snap.value.month));
const sparklinePath = computed(() => buildSparklinePath(snap.value.sparkline ?? []));

function walletUrl(walletId) {
    return buildPath(props.walletTransactionsPath, { walletId });
}
</script>

<template>
    <div class="space-y-6">
        <AppListToolbar>
            <p class="text-sm text-muted">{{ t("personal_finance.overview.subtitle", { month: monthLabel }) }}</p>
            <template #actions>
                <AppButton variant="ghost" size="md" :loading="loading" v-on:click="refresh">
                    <RefreshCw class="w-4 h-4" :stroke-width="2" />
                    {{ t("shared.common.refresh") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <AppMessage variant="info">
            {{ t("personal_finance.overview.help") }}
        </AppMessage>

        <!-- Hero KPI grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                    <Globe2 class="w-3.5 h-3.5" :stroke-width="2" />
                    <span>{{ t("personal_finance.overview.kpi.wallet_count") }}</span>
                </div>
                <p class="font-mono text-2xl text-primary mt-2">{{ snap.totals.walletCount }}</p>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                    <Wallet class="w-3.5 h-3.5" :stroke-width="2" />
                    <span>{{ t("personal_finance.overview.kpi.total_balance") }}</span>
                </div>
                <p class="font-mono text-2xl mt-2" :class="parseFloat(snap.totals.totalBalance) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                    {{ snap.totals.totalBalance }}
                </p>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                    <TrendingUp class="w-3.5 h-3.5 text-emerald-400" :stroke-width="2" />
                    <span>{{ t("personal_finance.overview.kpi.month_income") }}</span>
                </div>
                <p class="font-mono text-2xl text-emerald-400 mt-2">+{{ snap.monthFlow.income.current }}</p>
                <p class="text-xs mt-1" :class="deltaClass(snap.monthFlow.income.deltaPercent, false)">
                    {{ formatDelta(snap.monthFlow.income.deltaPercent) }}
                    <span class="text-muted ml-1">{{ t("personal_finance.overview.vs_previous_month") }}</span>
                </p>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                    <TrendingDown class="w-3.5 h-3.5 text-rose-400" :stroke-width="2" />
                    <span>{{ t("personal_finance.overview.kpi.month_expense") }}</span>
                </div>
                <p class="font-mono text-2xl text-rose-400 mt-2">-{{ snap.monthFlow.expense.current }}</p>
                <p class="text-xs mt-1" :class="deltaClass(snap.monthFlow.expense.deltaPercent, true)">
                    {{ formatDelta(snap.monthFlow.expense.deltaPercent) }}
                    <span class="text-muted ml-1">{{ t("personal_finance.overview.vs_previous_month") }}</span>
                </p>
            </div>
        </div>

        <!-- Month net banner -->
        <div class="bg-surface border border-line rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm">
                <Scale class="w-4 h-4 text-muted" :stroke-width="2" />
                <span class="text-muted">{{ t("personal_finance.overview.kpi.month_net") }}</span>
            </div>
            <span class="font-mono text-lg" :class="parseFloat(snap.totals.monthNet) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                {{ parseFloat(snap.totals.monthNet) >= 0 ? '+' : '' }}{{ snap.totals.monthNet }}
            </span>
        </div>

        <!-- Sparkline + top categories -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <section class="bg-surface border border-line rounded-lg p-4">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-3">{{ t("personal_finance.overview.spending_30d") }}</h3>
                <svg viewBox="0 0 200 40" preserveAspectRatio="none" class="w-full h-16">
                    <path
                        :d="sparklinePath"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="text-rose-400"
                    />
                </svg>
            </section>

            <section class="bg-surface border border-line rounded-lg p-4">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-3">{{ t("personal_finance.overview.category_breakdown") }}</h3>
                <ul v-if="snap.categoryBreakdown.length" class="space-y-2">
                    <li v-for="cat in snap.categoryBreakdown" :key="cat.categoryName">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-primary truncate">{{ cat.categoryName }}</span>
                            <span class="font-mono text-rose-400 ml-2">-{{ cat.total }}</span>
                        </div>
                        <div class="h-1 bg-line/40 rounded mt-1 overflow-hidden">
                            <div class="h-full bg-rose-400" :style="{ width: cat.percent + '%' }" />
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.overview.no_data_yet") }}</p>
            </section>
        </div>

        <!-- Wallets breakdown + Recent transactions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <section class="bg-surface border border-line rounded-lg p-4">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-3">{{ t("personal_finance.overview.wallets_breakdown") }}</h3>
                <ul v-if="snap.walletsBreakdown.length" class="space-y-3">
                    <li v-for="w in snap.walletsBreakdown" :key="w.id">
                        <a
                            :href="walletUrl(w.id)"
                            class="block px-2 py-1.5 -mx-2 rounded hover:bg-surface-2 transition-colors"
                            :title="t('personal_finance.overview.wallet_link_title', { name: w.name })"
                        >
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-primary truncate">{{ w.name }}</span>
                                <span class="font-mono shrink-0" :class="parseFloat(w.balance) >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ w.balance }}</span>
                            </div>
                            <div class="h-1 bg-line/40 rounded mt-1.5 overflow-hidden">
                                <div class="h-full bg-accent-500" :style="{ width: w.share + '%' }" />
                            </div>
                            <div class="flex items-center justify-between text-xs text-muted mt-1">
                                <span>+{{ w.monthIncome }} / -{{ w.monthExpense }}</span>
                                <span>{{ w.share }}%</span>
                            </div>
                        </a>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.overview.no_wallets") }}</p>
            </section>

            <section class="bg-surface border border-line rounded-lg p-4">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-3">{{ t("personal_finance.overview.recent_transactions") }}</h3>
                <ul v-if="snap.recentTransactions.length" class="divide-y divide-line/40">
                    <li v-for="tx in snap.recentTransactions" :key="tx.id" class="py-2 flex items-center justify-between text-sm">
                        <div class="min-w-0">
                            <p class="text-primary truncate">{{ tx.description ?? t("personal_finance.transactions.uncategorized") }}</p>
                            <p class="text-xs text-muted">
                                {{ formatDateShort(tx.date) }} ·
                                <a :href="walletUrl(tx.walletId)" class="hover:text-accent-400 transition-colors">{{ tx.walletName }}</a>
                                · {{ tx.categoryName ?? '—' }}
                            </p>
                        </div>
                        <span class="font-mono ml-3" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ signedAmount(tx) }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.overview.no_data_yet") }}</p>
            </section>
        </div>

        <!-- Goals + Upcoming recurring + Upcoming scheduled -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <section class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Target class="w-4 h-4 text-accent-400" :stroke-width="2" />
                    <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.overview.goals") }}</h3>
                </div>
                <p class="text-2xl text-primary">{{ snap.goals.activeCount }}<span class="text-sm text-muted ml-1">/ {{ snap.goals.totalCount }}</span></p>
                <p class="text-xs text-muted mb-3">{{ t("personal_finance.overview.goals_active_total") }}</p>
                <ul v-if="snap.goals.top.length" class="space-y-2 text-sm">
                    <li v-for="g in snap.goals.top" :key="g.id">
                        <div class="flex items-center justify-between">
                            <span class="truncate text-primary">{{ g.name }}</span>
                            <span class="text-xs text-muted">{{ g.progress }}%</span>
                        </div>
                        <div class="h-1 bg-line/40 rounded mt-1 overflow-hidden">
                            <div class="h-full" :style="{ width: g.progress + '%', backgroundColor: g.color ?? '#6366f1' }" />
                        </div>
                    </li>
                </ul>
            </section>

            <section class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Repeat class="w-4 h-4 text-accent-400" :stroke-width="2" />
                    <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.overview.upcoming_recurring") }}</h3>
                </div>
                <ul v-if="snap.upcomingRecurring.length" class="space-y-2 text-sm">
                    <li v-for="r in snap.upcomingRecurring" :key="r.id" class="flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-primary truncate">{{ r.description ?? '—' }}</p>
                            <p class="text-xs text-muted">{{ t("personal_finance.overview.day_of_month", { day: r.dayOfMonth }) }} · {{ r.walletName }}</p>
                        </div>
                        <span class="font-mono ml-2" :class="r.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ r.amount }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.overview.no_data_yet") }}</p>
            </section>

            <section class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Clock class="w-4 h-4 text-accent-400" :stroke-width="2" />
                    <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.overview.upcoming_scheduled") }}</h3>
                </div>
                <ul v-if="snap.upcomingScheduled.length" class="space-y-2 text-sm">
                    <li v-for="s in snap.upcomingScheduled" :key="s.id" class="flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-primary truncate">{{ s.description ?? '—' }}</p>
                            <p class="text-xs text-muted">{{ formatDateShort(s.scheduledDate) }} · {{ s.walletName }}</p>
                        </div>
                        <span class="font-mono ml-2" :class="s.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ s.amount }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.overview.no_data_yet") }}</p>
            </section>
        </div>

        <!-- Budget alerts -->
        <section v-if="snap.budgetAlerts.length" class="bg-surface border border-line rounded-lg p-4">
            <div class="flex items-center gap-2 mb-3">
                <AlertTriangle class="w-4 h-4 text-amber-400" :stroke-width="2" />
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.overview.budget_alerts") }}</h3>
            </div>
            <ul class="space-y-2 text-sm">
                <li v-for="alert in snap.budgetAlerts" :key="alert.itemId" class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-primary truncate">{{ alert.label }}</p>
                        <p class="text-xs text-muted">{{ alert.walletName }} · {{ t(`personal_finance.budget.sections.${alert.section}`) }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-mono text-rose-400">+{{ alert.overshoot }}</p>
                        <p class="text-xs text-muted">{{ alert.actual }} / {{ alert.expected }}</p>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
