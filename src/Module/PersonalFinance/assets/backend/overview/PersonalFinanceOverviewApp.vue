<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { RefreshCw, Wallet, TrendingUp, TrendingDown, Scale, Globe2 } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useOverviewData } from "./composables/useOverviewData.js";

const props = defineProps({
    snapshot: { type: Object, required: true },
    refreshPath: { type: String, required: true },
    walletTransactionsPath: { type: String, required: true },
});

const { t } = useI18n();
const { formatDateShort, formatMonthYear } = useDateFormat();
const { snapshot: snap, loading, refresh } = useOverviewData(props.refreshPath, props.snapshot);

const monthLabel = computed(() => formatMonthYear(snap.value.month));

function walletUrl(walletId) {
    return buildPath(props.walletTransactionsPath, { walletId });
}

function signedAmount(tx) {
    const sign = tx.type === "income" ? "+" : "-";
    return `${sign}${tx.amount}`;
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

        <!-- Hero KPIs -->
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
                <p class="font-mono text-2xl text-emerald-400 mt-2">+{{ snap.totals.monthIncome }}</p>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                    <TrendingDown class="w-3.5 h-3.5 text-rose-400" :stroke-width="2" />
                    <span>{{ t("personal_finance.overview.kpi.month_expense") }}</span>
                </div>
                <p class="font-mono text-2xl text-rose-400 mt-2">-{{ snap.totals.monthExpense }}</p>
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

        <!-- Wallets breakdown + Category breakdown -->
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
                                <div class="h-full bg-accent-500" :style="{ width: w.share + '%' }"></div>
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
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-3">{{ t("personal_finance.overview.category_breakdown") }}</h3>
                <ul v-if="snap.categoryBreakdown.length" class="space-y-2">
                    <li v-for="cat in snap.categoryBreakdown" :key="cat.categoryName">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-primary truncate">{{ cat.categoryName }}</span>
                            <span class="font-mono text-rose-400 ml-2">-{{ cat.total }}</span>
                        </div>
                        <div class="h-1 bg-line/40 rounded mt-1 overflow-hidden">
                            <div class="h-full bg-rose-400" :style="{ width: cat.percent + '%' }"></div>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted">{{ t("personal_finance.overview.no_data_yet") }}</p>
            </section>
        </div>

        <!-- Recent transactions cross-wallet -->
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
</template>
