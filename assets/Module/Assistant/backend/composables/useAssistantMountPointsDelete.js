import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Confirmation-modal-backed delete for mount points. The Aurora
 * convention forbids native `window.confirm()` — every destructive
 * action goes through an `AppModal` driven by a `deleting<Entity>` ref.
 */
export function useAssistantMountPointsDelete(mountPointList, deletePath) {
    const { t } = useI18n();
    const { request } = useRequest();

    const deletingMountPoint = ref(null);

    async function confirmDelete() {
        if (!deletingMountPoint.value) return;
        const data = await request(
            buildPath(deletePath, { id: deletingMountPoint.value.id }),
        );
        if (!data?.success) return;

        const id = deletingMountPoint.value.id;
        mountPointList.value = mountPointList.value.filter(
            (mountPoint) => mountPoint.id !== id,
        );
        toast.success(t("shared.common.deleted"));
        deletingMountPoint.value = null;
    }

    return { deletingMountPoint, confirmDelete };
}
