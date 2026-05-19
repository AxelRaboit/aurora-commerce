import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";

export function useInvoiceFilter(props, reload, search) {
    const { t } = useI18n();

    const statusFilter = ref("");
    const counts = ref(props.invoices.counts ?? {});

    function onStatusChange() {
        reload();
    }

    const STATUS_SELECT = computed(() =>
        props.statusOptions.map((option) => ({
            value: option.value,
            label: `${t(option.labelKey)} (${counts.value[option.value] ?? 0})`,
        })),
    );

    const exportXlsxUrl = computed(() => {
        const params = new URLSearchParams();
        if (search.value) params.set("search", search.value);
        if (statusFilter.value) params.set("status", statusFilter.value);
        const qs = params.toString();
        return qs ? `${props.exportXlsxPath}?${qs}` : props.exportXlsxPath;
    });

    return {
        statusFilter,
        counts,
        onStatusChange,
        STATUS_SELECT,
        exportXlsxUrl,
    };
}
