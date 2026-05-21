/**
 * Pointer-based drag for moving post-its on the board. Uses pointer
 * capture so the drag continues even when the cursor briefly leaves the
 * handle. The handle calls {@link startDrag}, which wires up move/up
 * listeners on itself and releases them in {@link endDrag}.
 */
export function usePostItDragDrop({ onMoveCommit }) {
    let dragState = null;

    function startDrag(event, note) {
        if (event.button !== undefined && event.button !== 0) return;
        event.preventDefault();
        const boardEl = event.currentTarget.closest(".post-it-board");
        if (!boardEl) return;
        const boardRect = boardEl.getBoundingClientRect();
        dragState = {
            note,
            offsetX: event.clientX - boardRect.left - note.positionX,
            offsetY: event.clientY - boardRect.top - note.positionY,
            boardRect,
            pointerId: event.pointerId,
        };
        event.currentTarget.setPointerCapture(event.pointerId);
        event.currentTarget.addEventListener("pointermove", onDragMove);
        event.currentTarget.addEventListener("pointerup", endDrag);
        event.currentTarget.addEventListener("pointercancel", endDrag);
    }

    function onDragMove(event) {
        if (!dragState) return;
        const x = event.clientX - dragState.boardRect.left - dragState.offsetX;
        const y = event.clientY - dragState.boardRect.top - dragState.offsetY;
        dragState.note.positionX = Math.max(0, Math.round(x));
        dragState.note.positionY = Math.max(0, Math.round(y));
    }

    function endDrag(event) {
        if (!dragState) return;
        const note = dragState.note;
        event.currentTarget.releasePointerCapture(dragState.pointerId);
        event.currentTarget.removeEventListener("pointermove", onDragMove);
        event.currentTarget.removeEventListener("pointerup", endDrag);
        event.currentTarget.removeEventListener("pointercancel", endDrag);
        dragState = null;
        onMoveCommit(note);
    }

    return { startDrag };
}
