import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";

export function usePlanningDelete(plannings, selectedPlanningId, deletePath) {
    const { t } = useI18n();
    const { request } = useRequest();

    const deletingPlanning = ref(null);

    async function confirmDelete() {
        if (!deletingPlanning.value) return;
        const data = await request(
            buildPath(deletePath, { id: deletingPlanning.value.id }),
        );
        if (!data?.success) return;

        const removedId = deletingPlanning.value.id;
        plannings.value = plannings.value.filter(
            (planning) => planning.id !== removedId,
        );
        if (Number(selectedPlanningId.value) === Number(removedId)) {
            selectedPlanningId.value = plannings.value[0]?.id ?? null;
        }
        toast.success(t("shared.common.deleted"));
        deletingPlanning.value = null;
    }

    return { deletingPlanning, confirmDelete };
}
