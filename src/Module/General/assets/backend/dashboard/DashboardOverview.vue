<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useFileSize } from "@/shared/composables/format/useFileSize.js";
import { statusBadge } from "@/shared/utils/format/statusStyles.js";
import AppChart from "@/shared/components/display/AppChart.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import { BarChart3, Building2, Camera, Clock, FileText, Image as ImageIcon, Menu as MenuIcon, Package, Receipt, ShoppingCart, Store, TrendingUp, Users } from "lucide-vue-next";
import { useDashboardModule } from "@general/backend/dashboard/composables/useDashboardModule.js";
import { useDashboardCharts } from "@general/backend/dashboard/composables/useDashboardCharts.js";

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    enabledModules: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { formatDateTime } = useDateFormat();
const { formatSize } = useFileSize();

const stats = computed(() => props.stats ?? {});
const enabledModules = computed(() => props.enabledModules);

const { activeModule, selectModule, visibleModules } = useDashboardModule(enabledModules);
const { postsByMonthData, dealsByStageData, hasDeals, productsByStatusData, hasProducts, invoicesByStatusData, hasInvoices, ordersByStatusData, hasOrders, formatCurrency, formatValue } =
    useDashboardCharts(stats);
</script>

<template>
    <div class="space-y-6">
        <div v-if="visibleModules.length === 0" class="flex flex-col items-center justify-center py-24 text-center text-secondary">
            <Package class="w-10 h-10 mb-3 opacity-30" :stroke-width="1.5" />
            <p class="text-sm">{{ t('backend.stats.noModuleEnabled') }}</p>
        </div>

        <template v-else>
            <div class="inline-flex p-1 bg-surface-2 border border-line rounded-lg gap-1 max-w-full overflow-x-auto scrollbar-thin">
                <AppTab
                    v-for="module in visibleModules"
                    :key="module.id"
                    size="sm"
                    :active="activeModule === module.id"
                    active-class="bg-surface text-primary shadow-sm"
                    inactive-class="text-secondary hover:text-primary"
                    class="whitespace-nowrap"
                    v-on:click="selectModule(module.id)"
                >
                    <component :is="module.icon" class="w-4 h-4" :stroke-width="2" />
                    {{ module.label() }}
                </AppTab>
            </div>

            <section v-show="activeModule === 'editorial'" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.posts') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-accent-600/10 flex items-center justify-center">
                                <FileText class="w-4 h-4 text-accent-500" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-accent-400">{{ stats.posts?.total ?? 0 }}</p>
                        <p class="text-xs text-muted mt-0.5">
                            {{ stats.posts?.published ?? 0 }} {{ t('backend.stats.published') }} ·
                            {{ stats.posts?.draft ?? 0 }} {{ t('backend.stats.draft') }}
                        </p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.media') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                                <ImageIcon class="w-4 h-4 text-sky-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-sky-400">{{ stats.media?.total ?? 0 }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ formatSize(stats.media?.totalSize ?? 0) }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.editorial.pendingComments') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <MenuIcon class="w-4 h-4 text-amber-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-amber-400">{{ stats.comments?.pending ?? 0 }}</p>
                        <p class="text-xs text-muted mt-0.5">
                            {{ stats.comments?.approved ?? 0 }} approuvés · {{ stats.comments?.spam ?? 0 }} spam
                        </p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.users') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <Users class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-emerald-400">{{ stats.users?.total ?? 0 }}</p>
                        <p class="text-xs text-muted mt-0.5">
                            {{ stats.posts?.scheduled ?? 0 }} {{ t('backend.stats.editorial.scheduled') }} ·
                            {{ stats.posts?.pendingReview ?? 0 }} {{ t('backend.stats.editorial.pendingReview') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div v-if="stats.postsByMonth?.length" class="lg:col-span-2 bg-surface border border-line/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.stats.postsByMonth') }}</h3>
                        <div class="h-56">
                            <AppChart type="line" :data="postsByMonthData" />
                        </div>
                    </div>
                    <div v-if="stats.posts?.byType?.length" class="bg-surface border border-line/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.stats.byType') }}</h3>
                        <div class="space-y-2">
                            <div v-for="item in stats.posts.byType" :key="item.slug" class="flex items-center justify-between text-sm">
                                <span class="text-secondary">{{ item.label }}</span>
                                <span class="font-medium text-primary tabular-nums">{{ item.count }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="stats.recentPosts?.length" class="bg-surface border border-line/60 rounded-xl p-5">
                    <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.stats.recent') }}</h3>
                    <div class="divide-y divide-line/40">
                        <div v-for="post in stats.recentPosts" :key="post.id" class="flex items-start justify-between gap-3 py-2.5 text-sm first:pt-0 last:pb-0">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-primary truncate">{{ post.title }}</div>
                                <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                    <span class="px-1.5 py-0.5 text-xs rounded-md shrink-0" :class="statusBadge(post.status)">{{ t(`backend.stats.postStatus.${post.status}`, post.status) }}</span>
                                    <span class="text-xs text-muted">{{ post.postType }} · {{ formatDateTime(post.updatedAt) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <Users class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.editorial.postsByAuthor') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <ImageIcon class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.editorial.mediaByType') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                </div>
            </section>

            <section v-show="activeModule === 'crm'" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.nav.contacts') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-accent-600/10 flex items-center justify-center">
                                <Users class="w-4 h-4 text-accent-500" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-accent-400">{{ stats.crm?.contacts ?? 0 }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.nav.companies') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                <Building2 class="w-4 h-4 text-violet-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-violet-400">{{ stats.crm?.companies ?? 0 }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.nav.deals') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <TrendingUp class="w-4 h-4 text-amber-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-amber-400">{{ stats.crm?.deals ?? 0 }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ formatValue(stats.crm?.pipelineValue) }} {{ t('backend.stats.pipeline') }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.won') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <TrendingUp class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-emerald-400">{{ formatValue(stats.crm?.wonValue) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div v-if="stats.crm?.recentDeals?.length" class="lg:col-span-2 bg-surface border border-line/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.stats.crm.recentDeals') }}</h3>
                        <div class="divide-y divide-line/40">
                            <div v-for="deal in stats.crm.recentDeals" :key="deal.id" class="flex items-start justify-between gap-3 py-2.5 text-sm first:pt-0 last:pb-0">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-primary truncate">{{ deal.name }}</div>
                                    <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                        <span class="px-1.5 py-0.5 text-xs rounded-md shrink-0" :class="statusBadge(deal.stage)">{{ t(`backend.crm.deals.stages.${deal.stage}`, deal.stage) }}</span>
                                        <span class="text-xs text-muted">{{ deal.contact ?? deal.company ?? '—' }} · {{ formatDateTime(deal.createdAt) }}</span>
                                    </div>
                                </div>
                                <span v-if="deal.value" class="text-sm font-semibold text-primary tabular-nums shrink-0">{{ formatValue(deal.value) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="hasDeals" class="bg-surface border border-line/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.stats.dealsByStage') }}</h3>
                        <div class="h-48">
                            <AppChart type="doughnut" :data="dealsByStageData" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <BarChart3 class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.crm.conversionRate') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <Clock class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.crm.recentActivity') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                </div>
            </section>

            <section v-show="activeModule === 'erp'" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.nav.products') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-accent-600/10 flex items-center justify-center">
                                <Package class="w-4 h-4 text-accent-500" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-accent-400">{{ stats.erp?.products ?? 0 }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ stats.erp?.active ?? 0 }} {{ t('backend.erp.products.status.active') }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.inventory') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                                <Package class="w-4 h-4 text-sky-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-sky-400">{{ formatCurrency(stats.erp?.inventoryCents) }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.erp.products.status.draft') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <Package class="w-4 h-4 text-amber-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-amber-400">{{ stats.erp?.draft ?? 0 }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.erp.outOfStock') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center">
                                <Package class="w-4 h-4 text-red-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-red-400">{{ stats.erp?.outOfStock ?? 0 }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div v-if="hasProducts" class="bg-surface border border-line/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.stats.productsByStatus') }}</h3>
                        <div class="h-48">
                            <AppChart type="doughnut" :data="productsByStatusData" />
                        </div>
                    </div>
                    <div v-if="stats.erp?.byType" class="lg:col-span-2 bg-surface border border-line/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.stats.erp.byType') }}</h3>
                        <div class="space-y-3">
                            <div v-for="(count, type) in stats.erp.byType" :key="type" class="flex items-center gap-3">
                                <div class="flex-1">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-secondary">{{ t(`backend.erp.products.types.${type}`, type) }}</span>
                                        <span class="font-medium text-primary tabular-nums">{{ count }}</span>
                                    </div>
                                    <div class="h-1.5 bg-surface-2 rounded-full overflow-hidden">
                                        <div class="h-full bg-accent-500/60 rounded-full" :style="{ width: stats.erp.products > 0 ? `${(count / stats.erp.products) * 100}%` : '0%' }" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <BarChart3 class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.erp.catalogEvolution') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <TrendingUp class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.erp.topSelling') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                </div>
            </section>

            <section v-show="activeModule === 'billing'" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.nav.invoices') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-accent-600/10 flex items-center justify-center">
                                <Receipt class="w-4 h-4 text-accent-500" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-accent-400">{{ stats.billing?.invoices ?? 0 }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.billing.totalGross') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <TrendingUp class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-emerald-400">{{ formatCurrency(stats.billing?.totalGrossCents) }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.billing.needingReview') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <Receipt class="w-4 h-4 text-amber-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-amber-400">{{ stats.billing?.needingReview ?? 0 }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.nav.suppliers') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                <Building2 class="w-4 h-4 text-violet-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-violet-400">{{ stats.billing?.suppliers ?? 0 }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ stats.billing?.ocrJobs ?? 0 }} {{ t('backend.nav.ocr_import') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div v-if="hasInvoices" class="bg-surface border border-line/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.billing.invoices.statusLabel') }}</h3>
                        <div class="h-48">
                            <AppChart type="doughnut" :data="invoicesByStatusData" />
                        </div>
                    </div>
                    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                            <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                                <Clock class="w-4 h-4 text-muted" :stroke-width="1.5" />
                            </div>
                            <p class="text-sm font-medium text-secondary">{{ t('backend.stats.billing.overdue') }}</p>
                            <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                        </div>
                        <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                            <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                                <Building2 class="w-4 h-4 text-muted" :stroke-width="1.5" />
                            </div>
                            <p class="text-sm font-medium text-secondary">{{ t('backend.stats.billing.topSuppliers') }}</p>
                            <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                        </div>
                        <div class="md:col-span-2 bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                            <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                                <BarChart3 class="w-4 h-4 text-muted" :stroke-width="1.5" />
                            </div>
                            <p class="text-sm font-medium text-secondary">{{ t('backend.stats.billing.monthlyEvolution') }}</p>
                            <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section v-show="activeModule === 'ecommerce'" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.nav.orders') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-accent-600/10 flex items-center justify-center">
                                <ShoppingCart class="w-4 h-4 text-accent-500" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-accent-400">{{ stats.ecommerce?.orders ?? 0 }}</p>
                        <p class="text-xs text-muted mt-0.5">
                            {{ stats.ecommerce?.byStatus?.pending ?? 0 }} {{ t('backend.stats.ecommerce.pending') }}
                        </p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.nav.listings') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <Store class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-emerald-400">{{ stats.ecommerce?.listings ?? 0 }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.ecommerce.revenue') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                <TrendingUp class="w-4 h-4 text-violet-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-violet-400">{{ formatCurrency(stats.ecommerce?.revenueCents) }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ t('backend.stats.ecommerce.revenueSubtitle') }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.ecommerce.averageOrder') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                                <Receipt class="w-4 h-4 text-sky-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-sky-400">{{ formatCurrency(stats.ecommerce?.averageOrderCents) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div v-if="stats.ecommerce?.recentOrders?.length" class="lg:col-span-2 bg-surface border border-line/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.stats.ecommerce.recentOrders') }}</h3>
                        <div class="divide-y divide-line/40">
                            <div v-for="order in stats.ecommerce.recentOrders" :key="order.id" class="flex items-start justify-between gap-3 py-2.5 text-sm first:pt-0 last:pb-0">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-primary truncate">{{ order.name }}</div>
                                    <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                        <span class="px-1.5 py-0.5 text-xs rounded-md shrink-0" :class="statusBadge(order.status)">{{ t(`backend.ecommerce.orders.status.${order.status}`, order.status) }}</span>
                                        <span class="text-xs text-muted">{{ order.number }} · {{ formatDateTime(order.createdAt) }}</span>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-primary tabular-nums shrink-0">{{ formatCurrency(order.totalCents) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="hasOrders" class="bg-surface border border-line/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-primary mb-4">{{ t('backend.stats.ecommerce.byStatus') }}</h3>
                        <div class="h-48">
                            <AppChart type="doughnut" :data="ordersByStatusData" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <BarChart3 class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.ecommerce.revenueChart') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <Clock class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.ecommerce.topProducts') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                </div>
            </section>

            <section v-show="activeModule === 'photo'" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.nav.galleries') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-accent-600/10 flex items-center justify-center">
                                <Camera class="w-4 h-4 text-accent-500" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-accent-400">{{ stats.photo?.galleries ?? 0 }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.media') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                                <ImageIcon class="w-4 h-4 text-sky-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-sky-400">{{ stats.photo?.photos ?? 0 }}</p>
                        <p class="text-xs text-muted mt-0.5">
                            {{ stats.photo?.galleries > 0 ? Math.round(stats.photo.photos / stats.photo.galleries) : 0 }} par galerie
                        </p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.photo.active') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                <Camera class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-emerald-400">{{ stats.photo?.active ?? 0 }}</p>
                    </div>
                    <div class="bg-surface border border-line rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('backend.stats.photo.finalized') }}</span>
                            <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                                <Camera class="w-4 h-4 text-violet-400" :stroke-width="2" />
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-violet-400">{{ stats.photo?.finalized ?? 0 }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <Users class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.photo.clientSelections') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                    <div class="bg-surface border border-dashed border-line rounded-xl p-5 flex flex-col items-center justify-center gap-2 text-center min-h-32">
                        <div class="w-9 h-9 rounded-lg bg-surface-2 flex items-center justify-center">
                            <BarChart3 class="w-4 h-4 text-muted" :stroke-width="1.5" />
                        </div>
                        <p class="text-sm font-medium text-secondary">{{ t('backend.stats.photo.byClient') }}</p>
                        <p class="text-xs text-muted">{{ t('backend.stats.comingSoon') }}</p>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>
