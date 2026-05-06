import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useMediaDragDrop(
    props,
    media,
    folders,
    currentFolderId,
    reorderEnabled,
) {
    const { t } = useI18n();

    const dragOverFolderId = ref(null);
    const dragOverMediaId = ref(null);
    const rootDragOver = ref(false);

    function onMediaDragStart(event, mediaItem) {
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(
            "application/x-aurora-media",
            String(mediaItem.id),
        );
    }

    function onFolderDragStart(event, folder) {
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(
            "application/x-aurora-folder",
            String(folder.id),
        );
    }

    function onFolderDragOver(event, folderId) {
        if (
            event.dataTransfer.types.includes("application/x-aurora-media") ||
            event.dataTransfer.types.includes("application/x-aurora-folder")
        ) {
            event.preventDefault();
            dragOverFolderId.value = folderId;
            rootDragOver.value = false;
        }
    }

    function onRootDragOver(event) {
        if (
            event.dataTransfer.types.includes("application/x-aurora-media") ||
            event.dataTransfer.types.includes("application/x-aurora-folder")
        ) {
            event.preventDefault();
            rootDragOver.value = true;
            dragOverFolderId.value = null;
        }
    }

    function onDragLeave() {
        dragOverFolderId.value = null;
        dragOverMediaId.value = null;
        rootDragOver.value = false;
    }

    function onMediaItemDragOver(event, mediaItem) {
        if (!event.dataTransfer.types.includes("application/x-aurora-media"))
            return;
        if (!reorderEnabled.value) return;
        event.preventDefault();
        event.stopPropagation();
        dragOverMediaId.value = mediaItem.id;
        dragOverFolderId.value = null;
    }

    async function reorderMedia(ids) {
        try {
            await fetch(props.reorderPath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ ids }),
            });
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function onMediaItemDrop(event, targetItem) {
        if (!reorderEnabled.value) return;
        event.preventDefault();
        event.stopPropagation();
        dragOverMediaId.value = null;
        const draggedId = Number(
            event.dataTransfer.getData("application/x-aurora-media"),
        );
        if (!draggedId || draggedId === targetItem.id) return;
        const list = [...media.value];
        const fromIdx = list.findIndex((m) => m.id === draggedId);
        const toIdx = list.findIndex((m) => m.id === targetItem.id);
        if (fromIdx === -1 || toIdx === -1) return;
        list.splice(toIdx, 0, list.splice(fromIdx, 1)[0]);
        list.forEach((item, index) => {
            item.position = index;
        });
        media.value = list;
        await reorderMedia(list.map((m) => m.id));
    }

    async function moveMedia(mediaId, folderId) {
        try {
            const response = await fetch(
                buildPath(props.movePath, { id: mediaId }),
                {
                    method: HttpMethod.Post,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ folderId }),
                },
            );
            const data = await response.json();
            if (!data.success) {
                toast.error(t("shared.common.error"));
                return;
            }
            if (folderId !== currentFolderId.value)
                media.value = media.value.filter((m) => m.id !== mediaId);
            else {
                const idx = media.value.findIndex((m) => m.id === mediaId);
                if (idx !== -1) media.value[idx] = data.media;
            }
            toast.success(t("backend.media.moved"));
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function moveFolder(folderId, newParentId) {
        if (folderId === newParentId) return;
        const folder = folders.value.find((f) => f.id === folderId);
        if (!folder) return;
        try {
            const response = await fetch(
                buildPath(props.folderEditPath, { id: folderId }),
                {
                    method: HttpMethod.Post,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        name: folder.name,
                        parentId: newParentId,
                    }),
                },
            );
            const data = await response.json();
            if (!data.success) {
                toast.error(data.errors?.parentId ?? t("shared.common.error"));
                return;
            }
            const idx = folders.value.findIndex((f) => f.id === folderId);
            if (idx !== -1) folders.value[idx] = data.folder;
            toast.success(t("backend.media.moved"));
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function onFolderDrop(event, targetFolderId) {
        event.preventDefault();
        const mediaId = event.dataTransfer.getData(
            "application/x-aurora-media",
        );
        const folderId = event.dataTransfer.getData(
            "application/x-aurora-folder",
        );
        dragOverFolderId.value = null;
        rootDragOver.value = false;
        if (mediaId) await moveMedia(Number(mediaId), targetFolderId);
        else if (folderId) await moveFolder(Number(folderId), targetFolderId);
    }

    return {
        dragOverFolderId,
        dragOverMediaId,
        rootDragOver,
        onMediaDragStart,
        onFolderDragStart,
        onFolderDragOver,
        onRootDragOver,
        onDragLeave,
        onMediaItemDragOver,
        onMediaItemDrop,
        onFolderDrop,
    };
}
