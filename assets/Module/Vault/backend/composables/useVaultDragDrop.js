import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@shared/utils/http/httpMethod.js";
import { buildPath } from "@shared/utils/http/buildPath.js";
import { getDescendantIds } from "@vault/backend/composables/useVaultTree.js";

const ENTRY_TYPE = "application/x-aurora-vault-entry";
const FOLDER_TYPE = "application/x-aurora-vault-folder";

export function useVaultDragDrop(props, entries, folders, currentFolderId) {
    const { t } = useI18n();

    const dragOverFolderId = ref(null);
    const rootDragOver = ref(false);

    function onEntryDragStart(event, entry) {
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(ENTRY_TYPE, String(entry.id));
    }

    function onFolderDragStart(event, folder) {
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(FOLDER_TYPE, String(folder.id));
    }

    function onFolderDragOver(event, folderId) {
        if (
            !event.dataTransfer.types.includes(ENTRY_TYPE) &&
            !event.dataTransfer.types.includes(FOLDER_TYPE)
        )
            return;
        event.preventDefault();
        dragOverFolderId.value = folderId;
        rootDragOver.value = false;
    }

    function onRootDragOver(event) {
        if (
            !event.dataTransfer.types.includes(ENTRY_TYPE) &&
            !event.dataTransfer.types.includes(FOLDER_TYPE)
        )
            return;
        event.preventDefault();
        rootDragOver.value = true;
        dragOverFolderId.value = null;
    }

    function onDragLeave() {
        dragOverFolderId.value = null;
        rootDragOver.value = false;
    }

    async function moveEntry(entryId, targetFolderId) {
        try {
            const response = await fetch(
                buildPath(props.moveEntryPath, { id: entryId }),
                {
                    method: HttpMethod.Post,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ folderId: targetFolderId }),
                },
            );
            const data = await response.json();
            if (!data.success) {
                toast.error(t("shared.common.error"));
                return;
            }

            const idx = entries.value.findIndex((e) => e.id === entryId);
            if (idx !== -1) entries.value[idx] = data.entry;
            toast.success(t("vault.entries.moved"));
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function moveFolder(folderId, newParentId) {
        if (folderId === newParentId) return;
        const descendantIds = getDescendantIds(folders.value, folderId);
        if (newParentId !== null && descendantIds.includes(newParentId)) return;

        const folder = folders.value.find((f) => f.id === folderId);
        if (!folder) return;

        try {
            const response = await fetch(
                buildPath(props.updateFolderPath, { id: folderId }),
                {
                    method: HttpMethod.Post,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        name: folder.name,
                        color: folder.color,
                        position: folder.position,
                        parentId: newParentId,
                    }),
                },
            );
            const data = await response.json();
            if (!data.success) {
                toast.error(t("shared.common.error"));
                return;
            }

            const idx = folders.value.findIndex((f) => f.id === folderId);
            if (idx !== -1) folders.value[idx] = data.folder;

            if (currentFolderId.value === folderId)
                currentFolderId.value = newParentId;
            toast.success(t("vault.folders.moved"));
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function onFolderDrop(event, targetFolderId) {
        event.preventDefault();
        dragOverFolderId.value = null;
        rootDragOver.value = false;

        const entryId = event.dataTransfer.getData(ENTRY_TYPE);
        const folderId = event.dataTransfer.getData(FOLDER_TYPE);

        if (entryId) await moveEntry(Number(entryId), targetFolderId);
        else if (folderId) await moveFolder(Number(folderId), targetFolderId);
    }

    return {
        dragOverFolderId,
        rootDragOver,
        onEntryDragStart,
        onFolderDragStart,
        onFolderDragOver,
        onRootDragOver,
        onDragLeave,
        onFolderDrop,
    };
}
