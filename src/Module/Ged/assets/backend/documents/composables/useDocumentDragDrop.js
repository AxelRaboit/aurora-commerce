import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

const DOC_MIME = "application/x-aurora-document";
const FOLDER_MIME = "application/x-aurora-document-folder";

/**
 * Drag&drop wiring for the documents sidebar:
 *   - Drag a document card onto a folder row → POST /move
 *   - Drag a folder onto another folder (or root) → POST /folders/{id}/move
 *
 * Uses distinct MIME types so a folder drag never gets confused with a doc
 * drag. After a successful move the affected document is filtered out of the
 * local list (when its new folder differs from the current view).
 */
export function useDocumentDragDrop(
    props,
    items,
    folders,
    currentFolderId,
    reload,
) {
    const { t } = useI18n();
    const { request: moveDocRequest } = useRequest();
    const { request: moveFolderRequest } = useRequest();

    const dragOverFolderId = ref(null);
    const rootDragOver = ref(false);

    function onDocumentDragStart(event, doc) {
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(DOC_MIME, String(doc.id));
    }

    function onFolderDragStart(event, folder) {
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(FOLDER_MIME, String(folder.id));
    }

    function onFolderDragOver(event, folderId) {
        const types = event.dataTransfer.types;
        if (types.includes(DOC_MIME) || types.includes(FOLDER_MIME)) {
            event.preventDefault();
            dragOverFolderId.value = folderId;
            rootDragOver.value = false;
        }
    }

    function onRootDragOver(event) {
        const types = event.dataTransfer.types;
        if (types.includes(DOC_MIME) || types.includes(FOLDER_MIME)) {
            event.preventDefault();
            rootDragOver.value = true;
            dragOverFolderId.value = null;
        }
    }

    function onDragLeave() {
        dragOverFolderId.value = null;
        rootDragOver.value = false;
    }

    async function moveDocument(docId, folderId) {
        const data = await moveDocRequest(
            buildPath(props.movePath, { id: docId }),
            { folderId },
        );
        if (!data) return;
        if (!data.success) {
            toast.error(t("shared.common.error"));
            return;
        }
        if (folderId !== currentFolderId.value) {
            items.value = items.value.filter((m) => m.id !== docId);
        } else {
            const idx = items.value.findIndex((m) => m.id === docId);
            if (idx !== -1) items.value[idx] = data.document;
        }
        toast.success(t("backend.ged.documents.moved"));
        // Refresh sidebar badge counts.
        reload?.();
    }

    async function moveFolder(folderId, newParentId) {
        if (folderId === newParentId) return;
        const folder = folders.value.find((f) => f.id === folderId);
        if (!folder) return;
        const data = await moveFolderRequest(
            buildPath(props.folderMovePath, { id: folderId }),
            { parentId: newParentId },
        );
        if (!data) return;
        if (!data.success) {
            toast.error(data.errors?.parentId ?? t("shared.common.error"));
            return;
        }
        if (Array.isArray(data.folders)) {
            const previousCounts = new Map(
                folders.value.map((f) => [f.id, f.documentCount ?? 0]),
            );
            folders.value = data.folders.map((f) => ({
                ...f,
                documentCount: previousCounts.get(f.id) ?? 0,
            }));
        }
        toast.success(t("backend.ged.documents.moved"));
    }

    async function onFolderDrop(event, targetFolderId) {
        event.preventDefault();
        const docId = event.dataTransfer.getData(DOC_MIME);
        const folderId = event.dataTransfer.getData(FOLDER_MIME);
        dragOverFolderId.value = null;
        rootDragOver.value = false;
        if (docId) await moveDocument(Number(docId), targetFolderId);
        else if (folderId) await moveFolder(Number(folderId), targetFolderId);
    }

    return {
        dragOverFolderId,
        rootDragOver,
        onDocumentDragStart,
        onFolderDragStart,
        onFolderDragOver,
        onRootDragOver,
        onDragLeave,
        onFolderDrop,
    };
}
