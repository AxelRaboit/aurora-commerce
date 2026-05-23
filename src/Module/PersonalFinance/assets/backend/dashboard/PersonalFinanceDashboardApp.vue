<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { RefreshCw, TrendingUp, TrendingDown, Wallet, Target, RotateCw, Clock, AlertTriangle } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import { useDashboardData } from "./composables/useDashboardData.js";
import { buildSparklinePath, deltaClass, formatDelta, signedAmount } from "./composables/dashboardFormatters.js";

const props = defineProps({
    snapshot: { type: Object, required: true },
    refreshPath: { type: String, required: true },
});

const { t } = useI18n();
const { snapshot: snap, loading, refresh } = useDashboardData(props.refreshPath, props.snapshot);

const sparklinePath = computed(() => buildSparklinePath(snap.value.sparkline ?? []));
</script>

<template>
    <div class="space-y-6">
        <AppListToolbar>
            <p class="text-sm text-muted">{{ t("personal_finance.dashboard.subtitle") }}</p>
            <template #actions>
                <AppButton variant="ghost" size="md" :loading="loading" v-on:click="refresh">
                    <RefreshCw class="w-4 h-4" :stroke-width="2" />
                    {{ t("shared.common.refresh") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <AppMessage variant="info">
            {{ t("personal_finance.dashboard.help") }}
        </AppMessage>

        <!-- KPI tiles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center justify-between text-xs text-muted">
                    <span class="uppercase tracking-wider">{{ t("personal_finance.dashboard.kpi.expense_month") }}</span>
                    <TrendingDown class="w-4 h-4 text-rose-400" :stroke-width="2" />
                </div>
                <p class="font-mono text-2xl text-primary mt-2">{{ snap.monthFlow.expense.current }}</p>
                <p class="text-xs mt-1" :class="deltaClass(snap.monthFlow.expense.deltaPercent, true)">
                    {{ formatDelta(snap.monthFlow.expense.deltaPercent) }}
                    <span class="text-muted ml-1">{{ t("personal_finance.dashboard.vs_previous_month") }}</span>
                </p>
            </div>
            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center justify-between text-xs text-muted">
                    <span class="uppercase tracking-wider">{{ t("personal_finance.dashboard.kpi.income_month") }}</span>
                    <TrendingUp class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                </div>
                <p class="font-mono text-2xl text-primary mt-2">{{ snap.monthFlow.income.current }}</p>
                <p class="text-xs mt-1" :class="deltaClass(snap.monthFlow.income.deltaPercent, false)">
                    {{ formatDelta(snap.monthFlow.income.deltaPercent) }}
                    <span class="text-muted ml-1">{{ t("personal_finance.dashboard.vs_previous_month") }}</span>
                </p>
            </div>
            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center justify-between text-xs text-muted">
                    <span class="uppercase tracking-wider">{{ t("personal_finance.dashboard.kpi.net_month") }}</span>
                </div>
                <p class="font-mono text-2xl mt-2" :class="parseFloat(snap.monthFlow.net) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                    {{ snap.monthFlow.net }}
                </p>
                <p class="text-xs text-muted mt-1">{{ t("personal_finance.dashboard.income_minus_expense") }}</p>
            </div>
            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center justify-between text-xs text-muted">
                    <span class="uppercase tracking-wider">{{ t("personal_finance.dashboard.kpi.total_balance") }}</span>
                    <Wallet class="w-4 h-4 text-accent-400" :stroke-width="2" />
                </div>
                <p class="font-mono text-2xl text-primary mt-2">{{ snap.walletStats.totalBalance }}</p>
                <p class="text-xs text-muted mt-1">{{ t("personal_finance.dashboard.across_wallets", { count: snap.walletStats.count }) }}</p>
            </div>
        </div>

        <!-- Sparkline + top categories -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <section class="bg-surface border border-line rounded-lg p-4">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-3">{{ t("personal_finance.dashboard.spending_30d") }}</h3>
                <svg viewBox="0 0 200 40" class="w-full h-16" preserveAspectRatio="none">
                    <path :d="sparklinePath" fill="none" stroke="currentColor" stroke-width="1.5" class="text-rose-400" />
                </svg>
            </section>

            <section class="bg-surface border border-line rounded-lg p-4">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-3">{{ t("personal_finance.dashboard.top_categories") }}</h3>
                <ul v-if="snap.topCategories.length" class="space-y-2">
                    <li v-for="cat in snap.topCategories" :key="cat.categoryId" class="text-sm">
                        <div class="flex items-baseline justify-between">
                            <span class="text-primary truncate">{{ cat.categoryName }}</span>
                            <span class="font-mono text-rose-400 ml-2">{{ cat.total }}</span>
                        </div>
                        <div class="h-1 bg-line/40 rounded mt-1 overflow-hidden">
                            <div class="h-full bg-rose-400" :style="{ width: cat.percent + '%' }"></div>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.dashboard.no_data_yet") }}</p>
            </section>
        </div>

        <!-- Pinned wallets + recent transactions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <section class="bg-surface border border-line rounded-lg p-4">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-3">{{ t("personal_finance.dashboard.pinned_wallets") }}</h3>
                <ul v-if="snap.pinnedWallets.length" class="space-y-2">
                    <li v-for="w in snap.pinnedWallets" :key="w.id" class="flex items-center justify-between text-sm">
                        <span class="text-primary">{{ w.name }}</span>
                        <span class="font-mono" :class="parseFloat(w.balance) >= 0 ? 'text-emerald-400' : 'text-rose-400'">{{ w.balance }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.dashboard.no_pinned_wallets") }}</p>
            </section>

            <section class="bg-surface border border-line rounded-lg p-4">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-3">{{ t("personal_finance.dashboard.recent_transactions") }}</h3>
                <ul v-if="snap.recentTransactions.length" class="divide-y divide-line/40">
                    <li v-for="tx in snap.recentTransactions" :key="tx.id" class="py-2 flex items-center justify-between text-sm">
                        <div class="min-w-0">
                            <p class="text-primary truncate">{{ tx.description ?? t("personal_finance.transactions.uncategorized") }}</p>
                            <p class="text-xs text-muted">{{ tx.date }} · {{ tx.walletName }} · {{ tx.categoryName ?? '—' }}</p>
                        </div>
                        <span class="font-mono ml-3" :class="tx.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ signedAmount(tx) }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.dashboard.no_data_yet") }}</p>
            </section>
        </div>

        <!-- Goals + Upcoming -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <section class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Target class="w-4 h-4 text-accent-400" :stroke-width="2" />
                    <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.dashboard.goals") }}</h3>
                </div>
                <p class="text-2xl text-primary">{{ snap.goals.activeCount }}<span class="text-sm text-muted ml-1">/ {{ snap.goals.totalCount }}</span></p>
                <p class="text-xs text-muted mb-3">{{ t("personal_finance.dashboard.goals_active_total") }}</p>
                <ul v-if="snap.goals.top.length" class="space-y-2 text-sm">
                    <li v-for="g in snap.goals.top" :key="g.id">
                        <div class="flex items-center justify-between">
                            <span class="truncate text-primary">{{ g.name }}</span>
                            <span class="text-xs text-muted">{{ g.progress }}%</span>
                        </div>
                        <div class="h-1 bg-line/40 rounded mt-1 overflow-hidden">
                            <div class="h-full" :style="{ width: g.progress + '%', backgroundColor: g.color ?? '#6366f1' }"></div>
                        </div>
                    </li>
                </ul>
            </section>

            <section class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 mb-3">
                    <RotateCw class="w-4 h-4 text-accent-400" :stroke-width="2" />
                    <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.dashboard.upcoming_recurring") }}</h3>
                </div>
                <ul v-if="snap.upcomingRecurring.length" class="space-y-2 text-sm">
                    <li v-for="r in snap.upcomingRecurring" :key="r.id" class="flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-primary truncate">{{ r.description ?? '—' }}</p>
                            <p class="text-xs text-muted">{{ t("personal_finance.dashboard.day_of_month", { day: r.dayOfMonth }) }} · {{ r.walletName }}</p>
                        </div>
                        <span class="font-mono ml-2" :class="r.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ r.amount }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.dashboard.no_data_yet") }}</p>
            </section>

            <section class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 mb-3">
                    <Clock class="w-4 h-4 text-accent-400" :stroke-width="2" />
                    <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.dashboard.upcoming_scheduled") }}</h3>
                </div>
                <ul v-if="snap.upcomingScheduled.length" class="space-y-2 text-sm">
                    <li v-for="s in snap.upcomingScheduled" :key="s.id" class="flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-primary truncate">{{ s.description ?? '—' }}</p>
                            <p class="text-xs text-muted font-mono">{{ s.scheduledDate }} · {{ s.walletName }}</p>
                        </div>
                        <span class="font-mono ml-2" :class="s.type === 'income' ? 'text-emerald-400' : 'text-rose-400'">{{ s.amount }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.dashboard.no_data_yet") }}</p>
            </section>
        </div>

        <!-- Budget alerts -->
        <section v-if="snap.budgetAlerts.length" class="bg-surface border border-line rounded-lg p-4">
            <div class="flex items-center gap-2 mb-3">
                <AlertTriangle class="w-4 h-4 text-amber-400" :stroke-width="2" />
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted">{{ t("personal_finance.dashboard.budget_alerts") }}</h3>
            </div>
            <ul class="space-y-2 text-sm">
                <li v-for="alert in snap.budgetAlerts" :key="`${alert.walletId}-${alert.itemId}`" class="flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-primary truncate">{{ alert.label }}</p>
                        <p class="text-xs text-muted">{{ alert.walletName }} · {{ t(`personal_finance.budget.sections.${alert.section}`) }}</p>
                    </div>
                    <div class="text-right ml-3">
                        <p class="font-mono text-rose-400">+{{ alert.overshoot }}</p>
                        <p class="text-xs text-muted">{{ alert.actual }} / {{ alert.expected }}</p>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
