/**
 * Pointer-based resize for post-its (bottom-right corner handle).
 * Independent state from {@link usePostItDragDrop} so a move and a
 * resize cannot stomp each other. Clamps to the provided min/max.
 */
export function usePostItResize({
    minWidth = 140,
    minHeight = 120,
    maxWidth = 600,
    maxHeight = 600,
    onResizeCommit,
} = {}) {
    let resizeState = null;

    function startResize(event, note) {
        if (event.button !== undefined && event.button !== 0) return;
        event.preventDefault();
        event.stopPropagation();
        resizeState = {
            note,
            startX: event.clientX,
            startY: event.clientY,
            startW: note.width,
            startH: note.height,
            pointerId: event.pointerId,
        };
        event.currentTarget.setPointerCapture(event.pointerId);
        event.currentTarget.addEventListener("pointermove", onResizeMove);
        event.currentTarget.addEventListener("pointerup", endResize);
        event.currentTarget.addEventListener("pointercancel", endResize);
    }

    function onResizeMove(event) {
        if (!resizeState) return;
        const dx = event.clientX - resizeState.startX;
        const dy = event.clientY - resizeState.startY;
        resizeState.note.width = Math.min(
            maxWidth,
            Math.max(minWidth, resizeState.startW + dx),
        );
        resizeState.note.height = Math.min(
            maxHeight,
            Math.max(minHeight, resizeState.startH + dy),
        );
    }

    function endResize(event) {
        if (!resizeState) return;
        const note = resizeState.note;
        event.currentTarget.releasePointerCapture(resizeState.pointerId);
        event.currentTarget.removeEventListener("pointermove", onResizeMove);
        event.currentTarget.removeEventListener("pointerup", endResize);
        event.currentTarget.removeEventListener("pointercancel", endResize);
        resizeState = null;
        onResizeCommit(note);
    }

    return { startResize };
}
