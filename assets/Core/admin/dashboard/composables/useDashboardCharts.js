import { computed } from "vue";
import { useI18n } from "vue-i18n";

const STAGE_COLORS = {
    lead: "#94a3b8",
    qualified: "#60a5fa",
    proposal: "#a78bfa",
    negotiation: "#fbbf24",
    won: "#34d399",
    lost: "#f87171",
};

const INVOICE_STATUS_COLORS = {
    draft: "#94a3b8",
    needs_review: "#fbbf24",
    validated: "#60a5fa",
    paid: "#34d399",
    archived: "#6b7280",
    credit_note: "#a78bfa",
};

const ORDER_STATUS_COLORS = {
    pending: "#fbbf24",
    paid: "#34d399",
    shipped: "#60a5fa",
    delivered: "#a78bfa",
    cancelled: "#f87171",
    refunded: "#94a3b8",
};

export function useDashboardCharts(stats) {
    const { t } = useI18n();

    const postsByMonthData = computed(() => {
        const series = stats.value.postsByMonth ?? [];
        return {
            labels: series.map((s) => s.month),
            datasets: [
                {
                    label: t("admin.stats.posts"),
                    data: series.map((s) => s.count),
                    borderColor: "#818cf8",
                    backgroundColor: "rgba(129, 140, 248, 0.15)",
                    fill: true,
                    tension: 0.3,
                },
            ],
        };
    });

    const dealsByStageData = computed(() => {
        const series = stats.value.crm?.dealsByStage ?? [];
        return {
            labels: series.map((s) => t(`admin.crm.deals.stages.${s.stage}`)),
            datasets: [
                {
                    data: series.map((s) => s.count),
                    backgroundColor: series.map(
                        (s) => STAGE_COLORS[s.stage] ?? "#94a3b8",
                    ),
                    borderWidth: 0,
                },
            ],
        };
    });

    const hasDeals = computed(() =>
        (stats.value.crm?.dealsByStage ?? []).some((s) => s.count > 0),
    );

    const productsByStatusData = computed(() => {
        const erp = stats.value.erp ?? {};
        return {
            labels: [
                t("admin.erp.products.status.draft"),
                t("admin.erp.products.status.active"),
                t("admin.erp.products.status.archived"),
            ],
            datasets: [
                {
                    data: [erp.draft ?? 0, erp.active ?? 0, erp.archived ?? 0],
                    backgroundColor: ["#fbbf24", "#34d399", "#94a3b8"],
                    borderWidth: 0,
                },
            ],
        };
    });

    const hasProducts = computed(() => (stats.value.erp?.products ?? 0) > 0);

    const invoicesByStatusData = computed(() => {
        const entries = Object.entries(
            stats.value.billing?.byStatus ?? {},
        ).filter(([, v]) => v > 0);
        return {
            labels: entries.map(([k]) =>
                t(`admin.billing.invoices.status.${k}`, k),
            ),
            datasets: [
                {
                    data: entries.map(([, v]) => v),
                    backgroundColor: entries.map(
                        ([k]) => INVOICE_STATUS_COLORS[k] ?? "#94a3b8",
                    ),
                    borderWidth: 0,
                },
            ],
        };
    });

    const hasInvoices = computed(
        () => (stats.value.billing?.invoices ?? 0) > 0,
    );

    const ordersByStatusData = computed(() => {
        const entries = Object.entries(
            stats.value.ecommerce?.byStatus ?? {},
        ).filter(([, v]) => v > 0);
        return {
            labels: entries.map(([k]) =>
                t(`admin.ecommerce.orders.status.${k}`, k),
            ),
            datasets: [
                {
                    data: entries.map(([, v]) => v),
                    backgroundColor: entries.map(
                        ([k]) => ORDER_STATUS_COLORS[k] ?? "#94a3b8",
                    ),
                    borderWidth: 0,
                },
            ],
        };
    });

    const hasOrders = computed(() => (stats.value.ecommerce?.orders ?? 0) > 0);

    const formatCurrency = (cents) =>
        new Intl.NumberFormat(undefined, {
            style: "currency",
            currency: "EUR",
        }).format((cents ?? 0) / 100);
    const formatValue = (value) =>
        new Intl.NumberFormat(undefined, {
            style: "currency",
            currency: "EUR",
            maximumFractionDigits: 0,
        }).format(value ?? 0);

    return {
        postsByMonthData,
        dealsByStageData,
        hasDeals,
        productsByStatusData,
        hasProducts,
        invoicesByStatusData,
        hasInvoices,
        ordersByStatusData,
        hasOrders,
        formatCurrency,
        formatValue,
    };
}
