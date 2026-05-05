import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";

export function useAgenciesDelete(agencyList, deletePath) {
    const { t } = useI18n();
    const { request } = useApiRequest();

    const deletingAgency = ref(null);

    async function confirmDelete() {
        if (!deletingAgency.value) return;
        const data = await request(
            buildPath(deletePath, { id: deletingAgency.value.id }),
        );
        if (data?.success) {
            agencyList.value = agencyList.value.filter(
                (agency) => agency.id !== deletingAgency.value.id,
            );
            toast.success(t("shared.common.deleted"));
            deletingAgency.value = null;
        }
    }

    return { deletingAgency, confirmDelete };
}
