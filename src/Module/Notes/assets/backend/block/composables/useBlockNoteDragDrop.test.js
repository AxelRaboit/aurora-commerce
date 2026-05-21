import { describe, it, expect, vi, beforeEach } from "vitest";

const toastErrorMock = vi.fn();

vi.mock("vue-i18n", () => ({
    useI18n: () => ({ t: (key) => key }),
}));

vi.mock("vue-sonner", () => ({
    toast: { error: toastErrorMock, success: vi.fn() },
}));

const { useBlockNoteDragDrop } = await import("./useBlockNoteDragDrop.js");

const DATA_TYPE = "application/x-aurora-block-note";

/**
 * Build a minimal DragEvent stand-in. We can't use the real DragEvent
 * constructor in jsdom because it doesn't expose `dataTransfer`. Tests
 * only call the methods the composable actually uses.
 */
function makeDragEvent({
    types = [DATA_TYPE],
    data = {},
    currentTarget = null,
    relatedTarget = null,
} = {}) {
    const store = { ...data };
    const preventDefault = vi.fn();
    const stopPropagation = vi.fn();
    return {
        preventDefault,
        stopPropagation,
        currentTarget,
        relatedTarget,
        dataTransfer: {
            types,
            effectAllowed: null,
            dropEffect: null,
            setData(type, value) {
                store[type] = value;
                if (!types.includes(type)) types.push(type);
            },
            getData(type) {
                return store[type] ?? "";
            },
        },
    };
}

function makeApi() {
    return {
        move: vi.fn().mockResolvedValue({ ok: true, payload: {} }),
    };
}

beforeEach(() => {
    toastErrorMock.mockReset();
});

describe("useBlockNoteDragDrop", () => {
    it("onDragStart stores the dragged id on the data transfer", () => {
        const api = makeApi();
        const refreshList = vi.fn();
        const dd = useBlockNoteDragDrop({ api, refreshList });

        const event = makeDragEvent({ types: [] });
        dd.onDragStart({ id: 42 }, event);

        expect(dd.draggingId.value).toBe(42);
        expect(event.dataTransfer.effectAllowed).toBe("move");
        expect(event.dataTransfer.getData(DATA_TYPE)).toBe("42");
    });

    it("onDragStart is a no-op when dataTransfer is missing", () => {
        const dd = useBlockNoteDragDrop({
            api: makeApi(),
            refreshList: vi.fn(),
        });

        dd.onDragStart({ id: 7 }, { dataTransfer: null });
        expect(dd.draggingId.value).toBeNull();
    });

    it("onDragEnd clears every drag state", () => {
        const dd = useBlockNoteDragDrop({
            api: makeApi(),
            refreshList: vi.fn(),
        });

        dd.draggingId.value = 1;
        dd.dragOverId.value = 2;
        dd.rootDragOver.value = true;
        dd.onDragEnd();

        expect(dd.draggingId.value).toBeNull();
        expect(dd.dragOverId.value).toBeNull();
        expect(dd.rootDragOver.value).toBe(false);
    });

    it("onDragOverNote sets dragOverId and prevents default for valid drags", () => {
        const dd = useBlockNoteDragDrop({
            api: makeApi(),
            refreshList: vi.fn(),
        });
        dd.draggingId.value = 1;

        const event = makeDragEvent();
        dd.onDragOverNote({ id: 5 }, event);

        expect(event.preventDefault).toHaveBeenCalled();
        expect(event.stopPropagation).toHaveBeenCalled();
        expect(event.dataTransfer.dropEffect).toBe("move");
        expect(dd.dragOverId.value).toBe(5);
        expect(dd.rootDragOver.value).toBe(false);
    });

    it("onDragOverNote ignores foreign MIME types", () => {
        const dd = useBlockNoteDragDrop({
            api: makeApi(),
            refreshList: vi.fn(),
        });

        const event = makeDragEvent({ types: ["text/plain"] });
        dd.onDragOverNote({ id: 5 }, event);

        expect(event.preventDefault).not.toHaveBeenCalled();
        expect(dd.dragOverId.value).toBeNull();
    });

    it("onDragOverNote ignores drag over self", () => {
        const dd = useBlockNoteDragDrop({
            api: makeApi(),
            refreshList: vi.fn(),
        });
        dd.draggingId.value = 5;

        const event = makeDragEvent();
        dd.onDragOverNote({ id: 5 }, event);

        expect(event.preventDefault).not.toHaveBeenCalled();
        expect(dd.dragOverId.value).toBeNull();
    });

    it("onDragLeaveNote clears dragOverId only when leaving the row entirely", () => {
        const dd = useBlockNoteDragDrop({
            api: makeApi(),
            refreshList: vi.fn(),
        });

        dd.dragOverId.value = 5;

        // related target is inside currentTarget -> keep
        const parent = { contains: (el) => el === "child" };
        dd.onDragLeaveNote(
            { id: 5 },
            { currentTarget: parent, relatedTarget: "child" },
        );
        expect(dd.dragOverId.value).toBe(5);

        // related target is outside -> clear
        dd.onDragLeaveNote(
            { id: 5 },
            { currentTarget: parent, relatedTarget: "outside" },
        );
        expect(dd.dragOverId.value).toBeNull();
    });

    it("onDragOverRoot marks rootDragOver and clears row hover", () => {
        const dd = useBlockNoteDragDrop({
            api: makeApi(),
            refreshList: vi.fn(),
        });

        dd.dragOverId.value = 3;
        const event = makeDragEvent();
        dd.onDragOverRoot(event);

        expect(event.preventDefault).toHaveBeenCalled();
        expect(event.dataTransfer.dropEffect).toBe("move");
        expect(dd.rootDragOver.value).toBe(true);
        expect(dd.dragOverId.value).toBeNull();
    });

    it("onDropOnNote moves the dragged id under the target and refreshes", async () => {
        const api = makeApi();
        const refreshList = vi.fn().mockResolvedValue();
        const dd = useBlockNoteDragDrop({ api, refreshList });

        const event = makeDragEvent({ data: { [DATA_TYPE]: "3" } });
        await dd.onDropOnNote({ id: 10 }, event);

        expect(event.preventDefault).toHaveBeenCalled();
        expect(api.move).toHaveBeenCalledWith(3, 10);
        expect(refreshList).toHaveBeenCalled();
        expect(dd.draggingId.value).toBeNull();
        expect(dd.dragOverId.value).toBeNull();
    });

    it("onDropOnNote ignores a drop on itself", async () => {
        const api = makeApi();
        const refreshList = vi.fn().mockResolvedValue();
        const dd = useBlockNoteDragDrop({ api, refreshList });

        const event = makeDragEvent({ data: { [DATA_TYPE]: "5" } });
        await dd.onDropOnNote({ id: 5 }, event);

        expect(api.move).not.toHaveBeenCalled();
        // refreshList is also skipped on this short-circuit
        expect(refreshList).not.toHaveBeenCalled();
    });

    it("onDropOnNote surfaces a cycle error via toast", async () => {
        const api = makeApi();
        api.move.mockResolvedValueOnce({
            ok: false,
            payload: { error: "cycle" },
        });
        const dd = useBlockNoteDragDrop({
            api,
            refreshList: vi.fn().mockResolvedValue(),
        });

        const event = makeDragEvent({ data: { [DATA_TYPE]: "3" } });
        await dd.onDropOnNote({ id: 10 }, event);

        expect(toastErrorMock).toHaveBeenCalledWith(
            "notes.block.errors.reorder_cycle",
        );
    });

    it("onDropOnNote surfaces a generic reorder error via toast", async () => {
        const api = makeApi();
        api.move.mockResolvedValueOnce({ ok: false, payload: {} });
        const dd = useBlockNoteDragDrop({
            api,
            refreshList: vi.fn().mockResolvedValue(),
        });

        const event = makeDragEvent({ data: { [DATA_TYPE]: "3" } });
        await dd.onDropOnNote({ id: 10 }, event);

        expect(toastErrorMock).toHaveBeenCalledWith(
            "notes.block.errors.reorder_failed",
        );
    });

    it("onDropOnRoot moves the dragged id to null parent", async () => {
        const api = makeApi();
        const refreshList = vi.fn().mockResolvedValue();
        const dd = useBlockNoteDragDrop({ api, refreshList });

        dd.rootDragOver.value = true;
        const event = makeDragEvent({ data: { [DATA_TYPE]: "7" } });
        await dd.onDropOnRoot(event);

        expect(api.move).toHaveBeenCalledWith(7, null);
        expect(refreshList).toHaveBeenCalled();
        expect(dd.rootDragOver.value).toBe(false);
        expect(dd.draggingId.value).toBeNull();
    });

    it("onDropOnRoot toasts on failure", async () => {
        const api = makeApi();
        api.move.mockResolvedValueOnce({ ok: false, payload: {} });
        const dd = useBlockNoteDragDrop({
            api,
            refreshList: vi.fn().mockResolvedValue(),
        });

        const event = makeDragEvent({ data: { [DATA_TYPE]: "7" } });
        await dd.onDropOnRoot(event);

        expect(toastErrorMock).toHaveBeenCalledWith(
            "notes.block.errors.reorder_failed",
        );
    });
});
