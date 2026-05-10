import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";

export function useServicesDelete(serviceList, deletePath) {
    const { t } = useI18n();
    const { request } = useRequest();

    const deletingService = ref(null);

    async function confirmDelete() {
        if (!deletingService.value) return;
        const data = await request(
            buildPath(deletePath, { id: deletingService.value.id }),
        );
        if (data?.success) {
            serviceList.value = serviceList.value.filter(
                (service) => service.id !== deletingService.value.id,
            );
            toast.success(t("shared.common.deleted"));
            deletingService.value = null;
        }
    }

    return { deletingService, confirmDelete };
}
