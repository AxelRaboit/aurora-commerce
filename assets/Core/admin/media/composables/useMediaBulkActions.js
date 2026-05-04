import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

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
    const bulkDeleteLoading = ref(false);

    async function doBulkDelete() {
        if (!selectedIds.value.size) return;
        bulkDeleteLoading.value = true;
        try {
            const res = await fetch(props.bulkDeletePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ ids: [...selectedIds.value] }),
            });
            if (!(await res.json()).success) throw new Error();
            media.value = media.value.filter(
                (m) => !selectedIds.value.has(m.id),
            );
            clearSelection();
            pendingBulkDelete.value = false;
            toast.success(t("admin.media.bulkDeleted"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            bulkDeleteLoading.value = false;
        }
    }

    const bulkMoveTargetId = ref(null);
    const openBulkMove = ref(false);

    async function bulkMove() {
        if (!selectedIds.value.size) return;
        try {
            const res = await fetch(props.bulkMovePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    ids: [...selectedIds.value],
                    folderId: bulkMoveTargetId.value,
                }),
            });
            if (!(await res.json()).success) throw new Error();
            if (bulkMoveTargetId.value !== currentFolderId.value) {
                media.value = media.value.filter(
                    (m) => !selectedIds.value.has(m.id),
                );
            }
            clearSelection();
            bulkMoveTargetId.value = null;
            toast.success(t("admin.media.bulkMoved"));
        } catch {
            toast.error(t("shared.common.error"));
        }
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
