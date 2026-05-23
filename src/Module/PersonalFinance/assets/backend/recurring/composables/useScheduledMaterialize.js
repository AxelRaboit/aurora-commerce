import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Spawn a real PersonalFinanceTransaction from a scheduled rule (the
 * user clicks "Materialise" when the planned event actually happens).
 * Materialisation is irreversible (no un-materialise endpoint), so we
 * route it through a confirmation modal — same shape as `useDelete`
 * (`pendingMaterialize` + `confirm` + `submit`) for consistency.
 */
export function useScheduledMaterialize(materializePath, onMaterialized) {
    const { t } = useI18n();
    const { loading, request } = useRequest();
    const pendingMaterialize = ref(null);

    function confirm(sched) {
        pendingMaterialize.value = sched;
    }

    async function submit() {
        if (!pendingMaterialize.value) return;
        const payload = await request(
            buildPath(materializePath, { id: pendingMaterialize.value.id }),
        );
        if (!payload || payload.success === false || !payload.scheduled) return;
        const result = payload.scheduled;
        pendingMaterialize.value = null;
        toast.success(t("personal_finance.recurring.materialized"));
        onMaterialized?.(result);
    }

    return { loading, pendingMaterialize, confirm, submit };
}
