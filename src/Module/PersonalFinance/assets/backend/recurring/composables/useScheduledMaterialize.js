import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Spawn a real PersonalFinanceTransaction from a scheduled rule (the
 * user clicks "Matérialiser" when the planned event actually happens).
 * Returns the updated scheduled row (now flagged generated).
 */
export function useScheduledMaterialize(materializePath, onMaterialized) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    async function materialize(sched) {
        const payload = await request(buildPath(materializePath, { id: sched.id }));
        if (!payload || payload.success === false || !payload.scheduled) return;
        toast.success(t("personal_finance.recurring.materialized"));
        onMaterialized?.(payload.scheduled);
    }

    return { loading, materialize };
}
