import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

const DATA_TYPE = "application/x-aurora-block-note";

/**
 * Native HTML5 drag-drop for the block-note tree. Same shape as the
 * Markdown version (drop on row = become child; drop on root = detach)
 * with its own MIME type so a markdown-note drag never lands here.
 */
export function useBlockNoteDragDrop({ api, refreshList }) {
    const { t } = useI18n();

    const draggingId = ref(null);
    const dragOverId = ref(null);
    const rootDragOver = ref(false);

    function onDragStart(note, event) {
        if (!event.dataTransfer) return;
        draggingId.value = note.id;
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData(DATA_TYPE, String(note.id));
    }

    function onDragEnd() {
        draggingId.value = null;
        dragOverId.value = null;
        rootDragOver.value = false;
    }

    function onDragOverNote(note, event) {
        if (!event.dataTransfer?.types.includes(DATA_TYPE)) return;
        if (note.id === draggingId.value) return;
        event.preventDefault();
        event.stopPropagation();
        event.dataTransfer.dropEffect = "move";
        dragOverId.value = note.id;
        rootDragOver.value = false;
    }

    function onDragLeaveNote(note, event) {
        const related = event.relatedTarget;
        if (related && event.currentTarget.contains(related)) return;
        if (dragOverId.value === note.id) dragOverId.value = null;
    }

    function onDragOverRoot(event) {
        if (!event.dataTransfer?.types.includes(DATA_TYPE)) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
        rootDragOver.value = true;
        dragOverId.value = null;
    }

    function onDragLeaveRoot(event) {
        const related = event.relatedTarget;
        if (related && event.currentTarget.contains(related)) return;
        rootDragOver.value = false;
    }

    async function onDropOnNote(targetNote, event) {
        event.preventDefault();
        event.stopPropagation();
        const draggedId = Number(event.dataTransfer.getData(DATA_TYPE));
        dragOverId.value = null;
        draggingId.value = null;
        if (!draggedId || draggedId === targetNote.id) return;

        const { ok, payload } = await api.move(draggedId, targetNote.id);
        if (!ok) {
            const msg =
                payload?.error === "cycle"
                    ? t("notes.block.errors.reorder_cycle")
                    : t("notes.block.errors.reorder_failed");
            toast.error(msg);
        }
        await refreshList();
    }

    async function onDropOnRoot(event) {
        event.preventDefault();
        const draggedId = Number(event.dataTransfer.getData(DATA_TYPE));
        rootDragOver.value = false;
        draggingId.value = null;
        if (!draggedId) return;

        const { ok } = await api.move(draggedId, null);
        if (!ok) {
            toast.error(t("notes.block.errors.reorder_failed"));
        }
        await refreshList();
    }

    return {
        draggingId,
        dragOverId,
        rootDragOver,
        onDragStart,
        onDragEnd,
        onDragOverNote,
        onDragLeaveNote,
        onDragOverRoot,
        onDragLeaveRoot,
        onDropOnNote,
        onDropOnRoot,
    };
}
