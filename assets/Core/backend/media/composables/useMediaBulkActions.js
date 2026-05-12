import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";

export function useMediaBulkActions(
    props,
    media,
    selectedIds,
    clearSelection,
    currentFolderId,
    displayedMedia,
) {
    const { t } = useI18n();

    function selectAll() {
        selectedIds.value = new Set(displayedMedia.value.map((m) => m.id));
    }

    const pendingBulkDelete = ref(false);
    const { loading: bulkDeleteLoading, request: bulkDeleteRequest } =
        useRequest();

    async function doBulkDelete() {
        if (!selectedIds.value.size) return;
        const res = await bulkDeleteRequest(props.bulkDeletePath, {
            ids: [...selectedIds.value],
        });
        if (!res) return;
        if (!res.success) {
            toast.error(t("shared.common.error"));
            return;
        }
        media.value = media.value.filter((m) => !selectedIds.value.has(m.id));
        clearSelection();
        pendingBulkDelete.value = false;
        toast.success(t("backend.media.bulkDeleted"));
    }

    const bulkMoveTargetId = ref(null);
    const openBulkMove = ref(false);

    const { request: bulkMoveRequest } = useRequest();

    async function bulkMove() {
        if (!selectedIds.value.size) return;
        const res = await bulkMoveRequest(props.bulkMovePath, {
            ids: [...selectedIds.value],
            folderId: bulkMoveTargetId.value,
        });
        if (!res) return;
        if (!res.success) {
            toast.error(t("shared.common.error"));
            return;
        }
        if (bulkMoveTargetId.value !== currentFolderId.value) {
            media.value = media.value.filter(
                (m) => !selectedIds.value.has(m.id),
            );
        }
        clearSelection();
        bulkMoveTargetId.value = null;
        toast.success(t("backend.media.bulkMoved"));
    }

    return {
        selectAll,
        pendingBulkDelete,
        bulkDeleteLoading,
        doBulkDelete,
        bulkMoveTargetId,
        openBulkMove,
        bulkMove,
    };
}
