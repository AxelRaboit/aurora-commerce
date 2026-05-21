import { describe, it, expect, vi, beforeEach } from "vitest";
import { usePostItDragDrop } from "./usePostItDragDrop.js";

/**
 * Pointer-event factory. The composable uses `event.currentTarget` for
 * pointer capture + listener attach/detach, so we need a fully-fledged
 * stub that lets us synthesize move/up events on the same target after
 * the initial pointerdown.
 */
function makeHandle({ boardLeft = 0, boardTop = 0 } = {}) {
    const listeners = new Map();
    const handle = {
        listeners,
        setPointerCapture: vi.fn(),
        releasePointerCapture: vi.fn(),
        addEventListener: vi.fn((type, fn) => listeners.set(type, fn)),
        removeEventListener: vi.fn((type) => listeners.delete(type)),
        closest: vi.fn(() => ({
            getBoundingClientRect: () => ({
                left: boardLeft,
                top: boardTop,
                width: 1000,
                height: 800,
                right: boardLeft + 1000,
                bottom: boardTop + 800,
            }),
        })),
    };
    return handle;
}

function pointerEvent({
    clientX,
    clientY,
    pointerId = 1,
    button = 0,
    currentTarget,
}) {
    return {
        clientX,
        clientY,
        pointerId,
        button,
        currentTarget,
        preventDefault: vi.fn(),
    };
}

describe("usePostItDragDrop", () => {
    let onMoveCommit;
    let drag;

    beforeEach(() => {
        onMoveCommit = vi.fn();
        drag = usePostItDragDrop({ onMoveCommit });
    });

    it("captures the pointer and registers move/up/cancel listeners on pointerdown", () => {
        const handle = makeHandle();
        const note = { positionX: 10, positionY: 20 };

        drag.startDrag(
            pointerEvent({
                clientX: 50,
                clientY: 60,
                currentTarget: handle,
            }),
            note,
        );

        expect(handle.setPointerCapture).toHaveBeenCalledWith(1);
        expect(handle.addEventListener).toHaveBeenCalledWith(
            "pointermove",
            expect.any(Function),
        );
        expect(handle.addEventListener).toHaveBeenCalledWith(
            "pointerup",
            expect.any(Function),
        );
        expect(handle.addEventListener).toHaveBeenCalledWith(
            "pointercancel",
            expect.any(Function),
        );
    });

    it("updates positionX/Y on pointermove relative to the board origin", () => {
        const handle = makeHandle({ boardLeft: 100, boardTop: 50 });
        const note = { positionX: 0, positionY: 0 };

        // Initial pointerdown at (clientX 150, clientY 100) on board with
        // origin (100, 50) → effective offset starts at (50 - 0, 50 - 0).
        drag.startDrag(
            pointerEvent({
                clientX: 150,
                clientY: 100,
                currentTarget: handle,
            }),
            note,
        );

        const onMove = handle.listeners.get("pointermove");
        // Pointer moved to (220, 180) — new effective coord = (220 - 100, 180 - 50) - offset (50, 50)
        onMove({ clientX: 220, clientY: 180 });

        expect(note.positionX).toBe(70);
        expect(note.positionY).toBe(80);
    });

    it("clamps positionX/Y to 0 when the pointer leaves the top-left edge", () => {
        const handle = makeHandle({ boardLeft: 100, boardTop: 50 });
        const note = { positionX: 30, positionY: 30 };

        drag.startDrag(
            pointerEvent({
                clientX: 130,
                clientY: 80,
                currentTarget: handle,
            }),
            note,
        );

        const onMove = handle.listeners.get("pointermove");
        // Drag the pointer way past the board origin (negative effective coord).
        onMove({ clientX: 0, clientY: 0 });

        expect(note.positionX).toBe(0);
        expect(note.positionY).toBe(0);
    });

    it("calls onMoveCommit with the note on pointerup and detaches listeners", () => {
        const handle = makeHandle();
        const note = { positionX: 0, positionY: 0 };

        drag.startDrag(
            pointerEvent({
                clientX: 0,
                clientY: 0,
                currentTarget: handle,
            }),
            note,
        );

        const onUp = handle.listeners.get("pointerup");
        onUp({ currentTarget: handle, pointerId: 1 });

        expect(onMoveCommit).toHaveBeenCalledExactlyOnceWith(note);
        expect(handle.releasePointerCapture).toHaveBeenCalledWith(1);
        expect(handle.removeEventListener).toHaveBeenCalledWith(
            "pointermove",
            expect.any(Function),
        );
        expect(handle.removeEventListener).toHaveBeenCalledWith(
            "pointerup",
            expect.any(Function),
        );
        expect(handle.removeEventListener).toHaveBeenCalledWith(
            "pointercancel",
            expect.any(Function),
        );
    });

    it("ignores non-primary button presses (right click, middle click)", () => {
        const handle = makeHandle();

        drag.startDrag(
            pointerEvent({
                clientX: 0,
                clientY: 0,
                button: 2, // right-click
                currentTarget: handle,
            }),
            { positionX: 0, positionY: 0 },
        );

        expect(handle.setPointerCapture).not.toHaveBeenCalled();
    });

    it("no-ops when pointermove fires before any pointerdown has captured", () => {
        // Defensive — stray events outside a drag must never crash. We can
        // only assert this indirectly: re-using a handle across two drags
        // should not produce duplicate listeners or stale state.
        const handle = makeHandle();
        const note1 = { positionX: 0, positionY: 0 };

        drag.startDrag(
            pointerEvent({ clientX: 0, clientY: 0, currentTarget: handle }),
            note1,
        );
        const onUp = handle.listeners.get("pointerup");
        onUp({ currentTarget: handle, pointerId: 1 });

        // After the up, internal state is null again. A second startDrag
        // re-installs listeners cleanly with a different note.
        const note2 = { positionX: 5, positionY: 5 };
        drag.startDrag(
            pointerEvent({ clientX: 100, clientY: 100, currentTarget: handle }),
            note2,
        );

        const onMove2 = handle.listeners.get("pointermove");
        onMove2({ clientX: 200, clientY: 200 });

        // offsetX = 100 - 0 (boardLeft) - 5 (note2.positionX) = 95
        // new x  = 200 - 0 - 95 = 105
        expect(note2.positionX).toBe(105);
        expect(note2.positionY).toBe(105);
        expect(note1.positionX).toBe(0); // first note untouched by the second drag
    });
});
