<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useDateFormat } from "@/shared/composables/useDateFormat.js";
import { useFileSize } from "@/shared/composables/useFileSize.js";
import { statusBadge } from "@/shared/utils/statusStyles.js";
import AppChart from "@/shared/components/AppChart.vue";
import {
    FileText,
    Image as ImageIcon,
    Menu as MenuIcon,
    Users,
    Building2,
    TrendingUp,
    Package,
} from "lucide-vue-next";

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { formatDateTime } = useDateFormat();
const { formatSize } = useFileSize();

const ACTIVE_MODULE_KEY = "velox-dashboard-module";
const MODULES = [
    { id: "editorial", label: () => t("admin.nav.sections.editorial"), icon: FileText },
    { id: "crm", label: () => t("admin.nav.sections.crm"), icon: Users },
    { id: "erp", label: () => t("admin.nav.sections.erp"), icon: Package },
];

const activeModule = ref(localStorage.getItem(ACTIVE_MODULE_KEY) || "editorial");

function selectModule(id) {
    activeModule.value = id;
    localStorage.setItem(ACTIVE_MODULE_KEY, id);
}

const stats = computed(() => props.stats ?? {});

// ── Editorial ──
const postsByMonthData = computed(() => {
    const series = stats.value.postsByMonth ?? [];
    return {
        labels: series.map(s => s.month),
        datasets: [{
            label: t("admin.stats.posts"),
            data: series.map(s => s.count),
            borderColor: "#818cf8",
            backgroundColor: "rgba(129, 140, 248, 0.15)",
            fill: true,
            tension: 0.3,
        }],
    };
});

// ── CRM ──
const STAGE_COLORS = {
    lead: "#94a3b8",
    qualified: "#60a5fa",
    proposal: "#a78bfa",
    negotiation: "#fbbf24",
    won: "#34d399",
    lost: "#f87171",
};

const dealsByStageData = computed(() => {
    const series = stats.value.crm?.dealsByStage ?? [];
    return {
        labels: series.map(s => t(`admin.crm.deals.stages.${s.stage}`)),
        datasets: [{
            data: series.map(s => s.count),
            backgroundColor: series.map(s => STAGE_COLORS[s.stage] ?? "#94a3b8"),
            borderWidth: 0,
        }],
    };
});

const hasDeals = computed(() => (stats.value.crm?.dealsByStage ?? []).some(s => s.count > 0));

// ── ERP ──
const productsByStatusData = computed(() => {
    const erp = stats.value.erp ?? {};
    return {
        labels: [
            t("admin.erp.products.status.draft"),
            t("admin.erp.products.status.active"),
            t("admin.erp.products.status.archived"),
        ],
        datasets: [{
            data: [erp.draft ?? 0, erp.active ?? 0, erp.archived ?? 0],
            backgroundColor: ["#fbbf24", "#34d399", "#94a3b8"],
            borderWidth: 0,
        }],
    };
});

const hasProducts = computed(() => (stats.value.erp?.products ?? 0) > 0);

const formatCurrency = (cents) => new Intl.NumberFormat(undefined, { style: "currency", currency: "EUR" }).format((cents ?? 0) / 100);
const formatValue = (value) => new Intl.NumberFormat(undefined, { style: "currency", currency: "EUR", maximumFractionDigits: 0 }).format(value ?? 0);
</script>

<template>
    <div class="space-y-6">
        <!-- Module pill nav -->
        <div class="inline-flex p-1 bg-surface-2 border border-line rounded-lg gap-1 max-w-full overflow-x-auto scrollbar-thin">
            <button
                v-for="module in MODULES"
                :key="module.id"
                type="button"
                class="flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition-colors whitespace-nowrap"
                :class="activeModule === module.id
                    ? 'bg-surface text-primary shadow-sm'
                    : 'text-secondary hover:text-primary'"
                v-on:click="selectModule(module.id)"
            >
                <component :is="module.icon" class="w-4 h-4" :stroke-width="2" />
                {{ module.label() }}
            </button>
        </div>

        <!-- ─── Editorial ─── -->
        <section v-show="activeModule === 'editorial'" class="space-y-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.posts') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <FileText class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-indigo-400">{{ stats.posts?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">
                        {{ stats.posts?.published ?? 0 }} {{ t('admin.stats.published') }} ·
                        {{ stats.posts?.draft ?? 0 }} {{ t('admin.stats.draft') }}
                    </p>
                </div>
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.media') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                            <ImageIcon class="w-4 h-4 text-sky-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-sky-400">{{ stats.media?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ formatSize(stats.media?.totalSize ?? 0) }}</p>
                </div>
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.menus') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <MenuIcon class="w-4 h-4 text-amber-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-amber-400">{{ stats.menus?.total ?? 0 }}</p>
                </div>
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.users') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <Users class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-400">{{ stats.users?.total ?? 0 }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div v-if="stats.postsByMonth?.length" class="lg:col-span-2 bg-surface border border-line/60 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-primary mb-4">{{ t('admin.stats.postsByMonth') }}</h3>
                    <div class="h-56">
                        <AppChart type="line" :data="postsByMonthData" />
                    </div>
                </div>

                <div v-if="stats.posts?.byType?.length" class="bg-surface border border-line/60 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-primary mb-4">{{ t('admin.stats.byType') }}</h3>
                    <div class="space-y-2">
                        <div v-for="item in stats.posts.byType" :key="item.slug" class="flex items-center justify-between text-sm">
                            <span class="text-secondary">{{ item.label }}</span>
                            <span class="font-medium text-primary tabular-nums">{{ item.count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="stats.recentPosts?.length" class="bg-surface border border-line/60 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-primary mb-4">{{ t('admin.stats.recent') }}</h3>
                <div class="space-y-3">
                    <div v-for="post in stats.recentPosts" :key="post.id" class="flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-primary truncate">{{ post.title }}</div>
                            <div class="text-xs text-muted">{{ post.postType }} · {{ formatDateTime(post.updatedAt) }}</div>
                        </div>
                        <span class="px-2 py-0.5 text-xs rounded-md" :class="statusBadge(post.status)">{{ t(`admin.stats.postStatus.${post.status}`, post.status) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── CRM ─── -->
        <section v-show="activeModule === 'crm'" class="space-y-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.nav.contacts') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <Users class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-indigo-400">{{ stats.crm?.contacts ?? 0 }}</p>
                </div>
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.nav.companies') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                            <Building2 class="w-4 h-4 text-violet-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-violet-400">{{ stats.crm?.companies ?? 0 }}</p>
                </div>
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.nav.deals') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <TrendingUp class="w-4 h-4 text-amber-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-amber-400">{{ stats.crm?.deals ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ formatValue(stats.crm?.pipelineValue) }} {{ t('admin.stats.pipeline') }}</p>
                </div>
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.won') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <TrendingUp class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-400">{{ formatValue(stats.crm?.wonValue) }}</p>
                </div>
            </div>

            <div v-if="hasDeals" class="bg-surface border border-line/60 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-primary mb-4">{{ t('admin.stats.dealsByStage') }}</h3>
                <div class="h-64">
                    <AppChart type="doughnut" :data="dealsByStageData" />
                </div>
            </div>
        </section>

        <!-- ─── ERP ─── -->
        <section v-show="activeModule === 'erp'" class="space-y-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.nav.products') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <Package class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-indigo-400">{{ stats.erp?.products ?? 0 }}</p>
                </div>
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.erp.products.status.active') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <Package class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-400">{{ stats.erp?.active ?? 0 }}</p>
                </div>
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.erp.products.status.draft') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <Package class="w-4 h-4 text-amber-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-amber-400">{{ stats.erp?.draft ?? 0 }}</p>
                </div>
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.inventory') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                            <Package class="w-4 h-4 text-sky-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-sky-400">{{ formatCurrency(stats.erp?.inventoryCents) }}</p>
                </div>
            </div>

            <div v-if="hasProducts" class="bg-surface border border-line/60 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-primary mb-4">{{ t('admin.stats.productsByStatus') }}</h3>
                <div class="h-64">
                    <AppChart type="doughnut" :data="productsByStatusData" />
                </div>
            </div>
        </section>
    </div>
</template>
