import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useUrlSearchSync } from "@/shared/composables/list/useUrlSearchSync.js";

export const ORDER_STATUS_BADGE = {
    pending: "amber",
    paid: "sky",
    shipped: "accent",
    delivered: "emerald",
    cancelled: "rose",
};

const STATUSES = ["pending", "paid", "shipped", "delivered", "cancelled"];

export function useOrderStatusFilter(props, reload) {
    const { t } = useI18n();
    const statusFilter = ref(props.currentStatus);
    const localStats = ref({ ...props.stats });
    const syncStatusUrl = useUrlSearchSync("status");

    function selectTab(status) {
        statusFilter.value = status;
        syncStatusUrl(status);
        reload();
    }

    const tabs = computed(() => [
        {
            key: "",
            label: t("backend.ecommerce.orders.tabs.all"),
            count: STATUSES.reduce(
                (sum, s) => sum + (localStats.value[s] ?? 0),
                0,
            ),
        },
        ...STATUSES.map((s) => ({
            key: s,
            label: t(`backend.ecommerce.orders.status.${s}`),
            count: localStats.value[s] ?? 0,
        })),
    ]);

    return { statusFilter, localStats, tabs, selectTab };
}

export function formatOrderTotal(order) {
    try {
        return new Intl.NumberFormat(undefined, {
            style: "currency",
            currency: order.currency,
        }).format(order.total);
    } catch {
        return `${order.total} ${order.currencySymbol}`;
    }
}
