<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { RefreshCw, TrendingUp, TrendingDown, Scale, BarChart3 } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useStatisticsData } from "./composables/useStatisticsData.js";
import { buildLinePath, buildMonthlyBars, deltaClass, formatDelta } from "./composables/statisticsFormatters.js";

const props = defineProps({
    snapshot: { type: Object, required: true },
    refreshPath: { type: String, required: true },
});

const { t } = useI18n();
const { formatMonthYear } = useDateFormat();
const { snapshot: snap, months, loading, refresh, setPeriod } = useStatisticsData(props.refreshPath, props.snapshot);

const monthlyChart = computed(() => buildMonthlyBars(snap.value.monthlyFlow ?? [], 600, 160));
const yoy = computed(() => snap.value.yoyComparison ?? {});
</script>

<template>
    <div class="space-y-6">
        <AppListToolbar>
            <div class="flex items-center gap-2">
                <AppTab v-for="period in snap.allowedPeriods" :key="period" variant="pill" size="sm" :active="months === period" v-on:click="setPeriod(period)">
                    {{ t("personal_finance.statistics.period_months", { months: period }) }}
                </AppTab>
            </div>
            <template #actions>
                <AppButton variant="ghost" size="md" :loading="loading" v-on:click="refresh">
                    <RefreshCw class="w-4 h-4" :stroke-width="2" />
                    {{ t("shared.common.refresh") }}
                </AppButton>
            </template>
        </AppListToolbar>

        <AppMessage variant="info">
            {{ t("personal_finance.statistics.help") }}
        </AppMessage>

        <!-- YoY KPI tiles -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                    <TrendingUp class="w-3.5 h-3.5 text-emerald-400" :stroke-width="2" />
                    <span>{{ t("personal_finance.statistics.kpi.yoy_income") }}</span>
                </div>
                <p class="font-mono text-2xl text-emerald-400 mt-2">+{{ yoy.income?.current ?? "0.00" }}</p>
                <p class="text-xs mt-1" :class="deltaClass(yoy.income?.deltaPercent, false)">
                    {{ formatDelta(yoy.income?.deltaPercent) }}
                    <span class="text-muted ml-1">{{ t("personal_finance.statistics.vs_last_year") }}</span>
                </p>
                <p class="text-xs text-muted mt-1">{{ t("personal_finance.statistics.previous_month_label", { month: yoy.lastYearMonth ? formatMonthYear(yoy.lastYearMonth) : "" }) }} : {{ yoy.income?.previous ?? "0.00" }}</p>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                    <TrendingDown class="w-3.5 h-3.5 text-rose-400" :stroke-width="2" />
                    <span>{{ t("personal_finance.statistics.kpi.yoy_expense") }}</span>
                </div>
                <p class="font-mono text-2xl text-rose-400 mt-2">-{{ yoy.expense?.current ?? "0.00" }}</p>
                <p class="text-xs mt-1" :class="deltaClass(yoy.expense?.deltaPercent, true)">
                    {{ formatDelta(yoy.expense?.deltaPercent) }}
                    <span class="text-muted ml-1">{{ t("personal_finance.statistics.vs_last_year") }}</span>
                </p>
                <p class="text-xs text-muted mt-1">{{ t("personal_finance.statistics.previous_month_label", { month: yoy.lastYearMonth ? formatMonthYear(yoy.lastYearMonth) : "" }) }} : {{ yoy.expense?.previous ?? "0.00" }}</p>
            </div>

            <div class="bg-surface border border-line rounded-lg p-4">
                <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-muted">
                    <Scale class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                    <span>{{ t("personal_finance.statistics.kpi.yoy_net") }}</span>
                </div>
                <p class="font-mono text-2xl mt-2" :class="parseFloat(yoy.net?.current ?? '0') >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                    {{ parseFloat(yoy.net?.current ?? "0") >= 0 ? "+" : "" }}{{ yoy.net?.current ?? "0.00" }}
                </p>
                <p class="text-xs text-muted mt-1">{{ t("personal_finance.statistics.previous_month_label", { month: yoy.lastYearMonth ? formatMonthYear(yoy.lastYearMonth) : "" }) }} : {{ yoy.net?.previous ?? "0.00" }}</p>
            </div>
        </div>

        <!-- Monthly income vs expense bar chart -->
        <section class="bg-surface border border-line rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium uppercase tracking-wider text-muted flex items-center gap-2">
                    <BarChart3 class="w-4 h-4 text-accent-400" :stroke-width="2" />
                    {{ t("personal_finance.statistics.monthly_flow") }}
                </h3>
                <div class="flex items-center gap-4 text-xs">
                    <span class="flex items-center gap-1.5 text-emerald-400">
                        <span class="inline-block w-3 h-3 bg-emerald-400 rounded-sm"></span>
                        {{ t("personal_finance.statistics.legend_income") }}
                    </span>
                    <span class="flex items-center gap-1.5 text-rose-400">
                        <span class="inline-block w-3 h-3 bg-rose-400 rounded-sm"></span>
                        {{ t("personal_finance.statistics.legend_expense") }}
                    </span>
                </div>
            </div>

            <div v-if="snap.monthlyFlow.length" class="max-w-3xl mx-auto">
                <svg viewBox="0 0 600 200" class="w-full h-auto">
                    <rect
                        v-for="(bar, i) in monthlyChart.bars"
                        :key="`b-${i}`"
                        :x="bar.x"
                        :y="bar.y"
                        :width="bar.width"
                        :height="bar.height"
                        rx="2"
                        :class="bar.kind === 'income' ? 'fill-emerald-400' : 'fill-rose-400'"
                    />
                    <text
                        v-for="(label, i) in monthlyChart.labels"
                        :key="`l-${i}`"
                        :x="label.x"
                        y="195"
                        text-anchor="middle"
                        class="fill-muted font-mono"
                        style="font-size: 10px;"
                    >{{ label.label }}</text>
                </svg>
            </div>
            <p v-else class="text-sm text-muted text-center py-8">{{ t("personal_finance.statistics.no_data_yet") }}</p>
        </section>

        <!-- Per-category trend (small multi) -->
        <section class="bg-surface border border-line rounded-lg p-4">
            <h3 class="text-sm font-medium uppercase tracking-wider text-muted mb-4">{{ t("personal_finance.statistics.category_trend") }}</h3>
            <ul v-if="snap.categoryTrend.length" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                <li v-for="cat in snap.categoryTrend" :key="cat.categoryName">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-primary truncate">{{ cat.categoryName }}</span>
                        <span class="font-mono text-rose-400 ml-2">-{{ cat.total }}</span>
                    </div>
                    <svg viewBox="0 0 200 40" preserveAspectRatio="none" class="w-full h-12 mt-1">
                        <path :d="buildLinePath(cat.series, 'expense')" fill="none" stroke="currentColor" stroke-width="2" class="text-rose-400" />
                    </svg>
                </li>
            </ul>
            <p v-else class="text-sm text-muted text-center py-8">{{ t("personal_finance.statistics.no_data_yet") }}</p>
        </section>
    </div>
</template>
