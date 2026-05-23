import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

/**
 * Spawn a real PersonalFinanceTransaction from a scheduled rule (the
 * user clicks "Matérialiser" when the planned event actually happens).
 * Returns the updated scheduled row (now flagged generated).
 */
export function useScheduledMaterialize(materializePath, onMaterialized) {
    const { t } = useI18n();
    const loading = ref(false);

    async function materialize(sched) {
        if (loading.value) return;
        loading.value = true;
        try {
            const url = buildPath(materializePath, { id: sched.id });
            const response = await fetch(url, { method: HttpMethod.Post });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false || !payload.scheduled) {
                toast.error(t("shared.common.error"));
                return;
            }
            toast.success(t("personal_finance.recurring.materialized"));
            onMaterialized?.(payload.scheduled);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { loading, materialize };
}
