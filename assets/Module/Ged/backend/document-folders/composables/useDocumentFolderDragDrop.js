import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Handles drag-and-drop reordering and reparenting of folder nodes.
 *
 * Drop zones:
 *   - top 30% of a row → insert BEFORE that folder (reorder, same parent)
 *   - middle 40%       → move INTO that folder (reparent)
 *   - bottom 30%       → insert AFTER that folder (reorder, same parent)
 *
 * After each successful operation the server returns the full updated list
 * via the `onSuccess(folders)` callback.
 */
export function useDocumentFolderDragDrop(movePath, reorderPath, onSuccess) {
    const { t } = useI18n();

    const draggingId = ref(null);
    const dropTarget = ref(null); // { id, zone: 'before'|'into'|'after' }

    function onDragStart(event, folder) {
        draggingId.value = folder.id;
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(
            "application/x-aurora-folder",
            String(folder.id),
        );
    }

    function getZone(event) {
        const rect = event.currentTarget.getBoundingClientRect();
        const y = event.clientY - rect.top;
        const ratio = y / rect.height;
        if (ratio < 0.3) return "before";
        if (ratio > 0.7) return "after";
        return "into";
    }

    function onDragOver(event, folder) {
        if (draggingId.value === null || draggingId.value === folder.id) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
        dropTarget.value = { id: folder.id, zone: getZone(event) };
    }

    function onDragLeave(event) {
        // Only clear if leaving the element entirely (not entering a child)
        if (!event.currentTarget.contains(event.relatedTarget)) {
            dropTarget.value = null;
        }
    }

    function onDragEnd() {
        draggingId.value = null;
        dropTarget.value = null;
    }

    async function onDrop(event, targetFolder, flatTree) {
        event.preventDefault();
        const zone = dropTarget.value?.zone ?? "into";
        dropTarget.value = null;

        const draggedId = draggingId.value;
        draggingId.value = null;

        if (!draggedId || draggedId === targetFolder.id) return;

        if (zone === "into") {
            await reparent(draggedId, targetFolder.id);
        } else {
            await reorderSiblings(draggedId, targetFolder, zone, flatTree);
        }
    }

    async function reparent(draggedId, newParentId) {
        const url = buildPath(movePath, { id: draggedId });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ parentId: newParentId }),
            });
            const data = await response.json();
            if (data.success) {
                onSuccess(data.folders ?? []);
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function reorderSiblings(draggedId, targetFolder, zone, flatTree) {
        // Build the new sibling order for the target's parent level
        const parentId = targetFolder.parentId ?? null;
        const siblings = flatTree
            .filter((node) => (node.parentId ?? null) === parentId)
            .map((node) => node.id);

        // Remove dragged from its current position and insert relative to target
        const filtered = siblings.filter((id) => id !== draggedId);
        const targetIdx = filtered.indexOf(targetFolder.id);
        if (targetIdx === -1) {
            // Dragged comes from a different level — reparent first
            await reparent(draggedId, parentId);
            return;
        }

        const insertIdx = zone === "before" ? targetIdx : targetIdx + 1;
        filtered.splice(insertIdx, 0, draggedId);

        try {
            const response = await fetch(reorderPath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ ids: filtered }),
            });
            const data = await response.json();
            if (data.success) {
                onSuccess(data.folders ?? []);
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return {
        draggingId,
        dropTarget,
        onDragStart,
        onDragOver,
        onDragLeave,
        onDragEnd,
        onDrop,
    };
}
