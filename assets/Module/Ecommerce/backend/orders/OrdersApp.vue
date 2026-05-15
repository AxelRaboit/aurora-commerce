<script setup>
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useOrderStatusFilter, ORDER_STATUS_BADGE, formatOrderTotal } from "./composables/useOrderStatusFilter.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import { Eye } from "lucide-vue-next";

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const props = defineProps({
    orders: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    currentStatus: { type: String, default: "" },
    stats: { type: Object, default: () => ({}) },
    showPath: { type: String, required: true },
    listPath: { type: String, required: true },
    /**
     * Extra fields config — kept for symmetry with the convention. The orders
     * list has no create/edit form (orders are created via the public checkout
     * flow), so the only meaningful slots here are extra-headers/extra-cells.
     */
    extraFields: { type: Object, default: () => ({}) },
});

const { items, loading, page, totalPages, search: searchInput, onSearch, reload, goToPage } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.orders,
        extraParams: () => ({ status: statusFilter.value || undefined }),
    },
);

const { statusFilter, localStats, tabs, selectTab } = useOrderStatusFilter(props, reload);
const statusBadge = (status) => ORDER_STATUS_BADGE[status] ?? "slate";
const formatTotal = (order) => formatOrderTotal(order);
</script>

<template>
    <div class="space-y-4">
        <div class="flex gap-1 flex-wrap">
            <AppTab
                v-for="tab in tabs"
                :key="tab.key"
                size="sm"
                :active="statusFilter === tab.key"
                v-on:click="selectTab(tab.key)"
            >
                {{ tab.label }}
                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full text-xs" :class="statusFilter === tab.key ? 'bg-accent-600/25' : 'bg-surface-3'">
                    {{ tab.count }}
                </span>
            </AppTab>
        </div>

        <AppSearchInput
            v-model="searchInput"
            :placeholder="t('backend.ecommerce.orders.searchPlaceholder')"
            v-on:search="onSearch"
        />

        <div class="relative space-y-4">
        <div class="sm:hidden space-y-2">
            <AppNoData v-if="!items?.length" :message="t('backend.ecommerce.orders.empty')" />
            <div v-for="order in items" :key="order.id" class="bg-surface border border-line rounded-xl p-4 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-semibold text-primary">{{ order.number }}</p>
                        <p class="text-xs text-secondary truncate">{{ order.name }}</p>
                        <p class="text-xs text-muted truncate">{{ order.email }}</p>
                    </div>
                    <AppBadge :color="statusBadge(order.status)" class="shrink-0">{{ t(`backend.ecommerce.orders.status.${order.status}`) }}</AppBadge>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line/40">
                    <span class="text-xs text-muted">{{ formatDateShort(order.createdAt) }} · {{ order.itemCount }} {{ t('backend.ecommerce.orders.items') }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-primary">{{ formatTotal(order) }}</span>
                        <AppIconButton color="sky" :title="t('shared.common.view')" :href="buildPath(showPath, { id: order.id })">
                            <Eye class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.orders.number') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.orders.customer') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.ecommerce.orders.date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.orders.status_col') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.ecommerce.orders.total') }}</th>
                        <slot name="extra-headers" />
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="order in items" :key="order.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3 font-mono text-primary">{{ order.number }}</td>
                        <td class="px-6 py-3">
                            <p class="text-primary">{{ order.name }}</p>
                            <p class="text-xs text-muted">{{ order.email }}</p>
                        </td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ formatDateShort(order.createdAt) }}</td>
                        <td class="px-6 py-3">
                            <AppBadge :color="statusBadge(order.status)">{{ t(`backend.ecommerce.orders.status.${order.status}`) }}</AppBadge>
                        </td>
                        <td class="px-6 py-3 text-right font-semibold text-primary">{{ formatTotal(order) }}</td>
                        <slot name="extra-cells" :order="order" />
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="sky" :title="t('shared.common.view')" :href="buildPath(showPath, { id: order.id })">
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('backend.ecommerce.orders.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
        <AppLoader :active="loading" />
        </div>
    </div>
</template>
