<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/useListPage.js";
import { useUrlSearchSync } from "@/shared/composables/useUrlSearchSync.js";
import { useDateFormat } from "@/shared/composables/useDateFormat.js";
import AppSearchInput from "@/shared/components/AppSearchInput.vue";
import AppPagination from "@/shared/components/AppPagination.vue";
import AppNoData from "@/shared/components/AppNoData.vue";
import AppBadge from "@/shared/components/AppBadge.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
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
});

const STATUSES = ["pending", "paid", "shipped", "delivered", "cancelled"];

const statusFilter = ref(props.currentStatus);
const localStats = ref({ ...props.stats });
const syncStatusUrl = useUrlSearchSync("status");

const { items, page, totalPages, search: searchInput, onSearch, reload, goToPage } = useListPage(
    props.listPath,
    {
        initialSearch: props.search,
        initialData: props.orders,
        extraParams: () => ({ status: statusFilter.value || undefined }),
    },
);

function selectTab(status) {
    statusFilter.value = status;
    syncStatusUrl(status);
    reload();
}

const tabs = computed(() => [
    { key: "", label: t("admin.ecommerce.orders.tabs.all"), count: STATUSES.reduce((sum, s) => sum + (localStats.value[s] ?? 0), 0) },
    ...STATUSES.map((s) => ({ key: s, label: t(`admin.ecommerce.orders.status.${s}`), count: localStats.value[s] ?? 0 })),
]);

const statusBadge = (status) => ({
    pending: "amber",
    paid: "sky",
    shipped: "accent",
    delivered: "emerald",
    cancelled: "rose",
}[status] ?? "slate");

function formatTotal(order) {
    try {
        return new Intl.NumberFormat(undefined, { style: "currency", currency: order.currency }).format(order.total);
    } catch {
        return `${order.total} ${order.currencySymbol}`;
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex gap-1 flex-wrap">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                :class="statusFilter === tab.key ? 'bg-accent-600/15 text-accent-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                v-on:click="selectTab(tab.key)"
            >
                {{ tab.label }}
                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full text-xs" :class="statusFilter === tab.key ? 'bg-accent-600/25' : 'bg-surface-3'">
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <AppSearchInput
            v-model="searchInput"
            :placeholder="t('admin.ecommerce.orders.searchPlaceholder')"
            v-on:search="onSearch"
        />

        <div class="sm:hidden space-y-2">
            <AppNoData v-if="!items?.length" :message="t('admin.ecommerce.orders.empty')" />
            <div v-for="order in items" :key="order.id" class="bg-surface border border-line rounded-xl p-4 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-semibold text-primary">{{ order.number }}</p>
                        <p class="text-xs text-secondary truncate">{{ order.name }}</p>
                        <p class="text-xs text-muted truncate">{{ order.email }}</p>
                    </div>
                    <AppBadge :color="statusBadge(order.status)" class="shrink-0">{{ t(`admin.ecommerce.orders.status.${order.status}`) }}</AppBadge>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line/40">
                    <span class="text-xs text-muted">{{ formatDateShort(order.createdAt) }} · {{ order.itemCount }} {{ t('admin.ecommerce.orders.items') }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-primary">{{ formatTotal(order) }}</span>
                        <AppIconButton color="sky" :title="t('shared.common.view')" :href="showPath.replace('__id__', order.id)">
                            <Eye class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.orders.number') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.orders.customer') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.ecommerce.orders.date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.orders.status_col') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.ecommerce.orders.total') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="order in items" :key="order.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-4 py-3 font-mono text-primary">{{ order.number }}</td>
                        <td class="px-4 py-3">
                            <p class="text-primary">{{ order.name }}</p>
                            <p class="text-xs text-muted">{{ order.email }}</p>
                        </td>
                        <td class="px-4 py-3 text-secondary hidden md:table-cell">{{ formatDateShort(order.createdAt) }}</td>
                        <td class="px-4 py-3">
                            <AppBadge :color="statusBadge(order.status)">{{ t(`admin.ecommerce.orders.status.${order.status}`) }}</AppBadge>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-primary">{{ formatTotal(order) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="sky" :title="t('shared.common.view')" :href="showPath.replace('__id__', order.id)">
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.ecommerce.orders.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
    </div>
</template>
