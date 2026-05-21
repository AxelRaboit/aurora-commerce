import { describe, it, expect, vi, beforeEach } from "vitest";
import { usePostItResize } from "./usePostItResize.js";

function makeHandle() {
    const listeners = new Map();
    return {
        listeners,
        setPointerCapture: vi.fn(),
        releasePointerCapture: vi.fn(),
        addEventListener: vi.fn((type, fn) => listeners.set(type, fn)),
        removeEventListener: vi.fn((type) => listeners.delete(type)),
    };
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
        stopPropagation: vi.fn(),
    };
}

describe("usePostItResize", () => {
    let onResizeCommit;
    let resize;

    beforeEach(() => {
        onResizeCommit = vi.fn();
        resize = usePostItResize({ onResizeCommit });
    });

    it("captures the pointer and registers listeners on pointerdown", () => {
        const handle = makeHandle();
        const note = { width: 200, height: 200 };

        resize.startResize(
            pointerEvent({ clientX: 100, clientY: 100, currentTarget: handle }),
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
    });

    it("expands width/height by the pointer delta", () => {
        const handle = makeHandle();
        const note = { width: 220, height: 220 };

        resize.startResize(
            pointerEvent({ clientX: 100, clientY: 100, currentTarget: handle }),
            note,
        );
        const onMove = handle.listeners.get("pointermove");
        onMove({ clientX: 150, clientY: 130 });

        expect(note.width).toBe(270); // 220 + 50
        expect(note.height).toBe(250); // 220 + 30
    });

    it("clamps to the default minimum (140 × 120)", () => {
        const handle = makeHandle();
        const note = { width: 200, height: 200 };

        resize.startResize(
            pointerEvent({ clientX: 100, clientY: 100, currentTarget: handle }),
            note,
        );
        const onMove = handle.listeners.get("pointermove");
        // Drag way to the upper-left — would shrink below the minimum.
        onMove({ clientX: 0, clientY: 0 });

        expect(note.width).toBe(140);
        expect(note.height).toBe(120);
    });

    it("clamps to the default maximum (600 × 600)", () => {
        const handle = makeHandle();
        const note = { width: 300, height: 300 };

        resize.startResize(
            pointerEvent({ clientX: 100, clientY: 100, currentTarget: handle }),
            note,
        );
        const onMove = handle.listeners.get("pointermove");
        // Drag way down-right — would exceed the maximum.
        onMove({ clientX: 9999, clientY: 9999 });

        expect(note.width).toBe(600);
        expect(note.height).toBe(600);
    });

    it("respects custom min/max overrides", () => {
        const custom = usePostItResize({
            minWidth: 50,
            minHeight: 50,
            maxWidth: 800,
            maxHeight: 800,
            onResizeCommit,
        });
        const handle = makeHandle();
        const note = { width: 100, height: 100 };

        custom.startResize(
            pointerEvent({ clientX: 0, clientY: 0, currentTarget: handle }),
            note,
        );
        const onMove = handle.listeners.get("pointermove");
        onMove({ clientX: -200, clientY: -200 });

        expect(note.width).toBe(50);
        expect(note.height).toBe(50);
    });

    it("calls onResizeCommit on pointerup and detaches listeners", () => {
        const handle = makeHandle();
        const note = { width: 200, height: 200 };

        resize.startResize(
            pointerEvent({ clientX: 100, clientY: 100, currentTarget: handle }),
            note,
        );
        const onUp = handle.listeners.get("pointerup");
        onUp({ currentTarget: handle, pointerId: 1 });

        expect(onResizeCommit).toHaveBeenCalledExactlyOnceWith(note);
        expect(handle.releasePointerCapture).toHaveBeenCalledWith(1);
        expect(handle.removeEventListener).toHaveBeenCalledWith(
            "pointermove",
            expect.any(Function),
        );
    });

    it("prevents the drag (which lives on a parent) from intercepting", () => {
        const handle = makeHandle();
        const event = pointerEvent({
            clientX: 0,
            clientY: 0,
            currentTarget: handle,
        });

        resize.startResize(event, { width: 200, height: 200 });

        // stopPropagation is what keeps the parent's pointerdown listener
        // (the drag handler) from claiming the same gesture.
        expect(event.stopPropagation).toHaveBeenCalled();
    });

    it("ignores non-primary button presses", () => {
        const handle = makeHandle();

        resize.startResize(
            pointerEvent({
                clientX: 0,
                clientY: 0,
                button: 2,
                currentTarget: handle,
            }),
            { width: 200, height: 200 },
        );

        expect(handle.setPointerCapture).not.toHaveBeenCalled();
    });
});
