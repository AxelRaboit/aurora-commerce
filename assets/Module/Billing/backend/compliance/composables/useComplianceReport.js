import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { ShieldCheck, ShieldAlert, ShieldX } from "lucide-vue-next";

export function useComplianceReport(reportPath) {
    const { t } = useI18n();

    const report = ref(null);
    const loading = ref(false);
    const error = ref(false);
    const expanded = ref({ sequence: true, archive: false, audit: false });

    const overallColor = computed(
        () =>
            ({
                ok: "emerald",
                warning: "amber",
                error: "rose",
            })[report.value?.overall] ?? "slate",
    );

    const overallIcon = computed(
        () =>
            ({
                ok: ShieldCheck,
                warning: ShieldAlert,
                error: ShieldX,
            })[report.value?.overall] ?? ShieldCheck,
    );

    function statusColor(s) {
        return { ok: "emerald", warning: "amber", error: "rose" }[s] ?? "slate";
    }

    function statusLabel(s) {
        return t(`backend.billing.compliance.statusLabels.${s}`);
    }

    async function load() {
        loading.value = true;
        error.value = false;
        try {
            const res = await fetch(reportPath);
            const data = await res.json();
            if (data.success) {
                report.value = data;
                expanded.value.sequence = data.checks.sequence.status !== "ok";
                expanded.value.archive = data.checks.archive.status !== "ok";
                expanded.value.audit = data.checks.audit.status !== "ok";
            } else {
                error.value = true;
            }
        } catch {
            error.value = true;
        } finally {
            loading.value = false;
        }
    }

    onMounted(load);

    return {
        report,
        loading,
        error,
        expanded,
        overallColor,
        overallIcon,
        statusColor,
        statusLabel,
        load,
    };
}
