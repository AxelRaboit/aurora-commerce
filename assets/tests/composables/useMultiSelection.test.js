import { describe, expect, it } from "vitest";
import { useMultiSelection } from "@/shared/composables/list/useMultiSelection.js";

describe("useMultiSelection", () => {
    it("starts empty", () => {
        const { selectedIds, isSelecting } = useMultiSelection();
        expect(selectedIds.value.size).toBe(0);
        expect(isSelecting.value).toBe(false);
    });

    it("toggles ids in and out", () => {
        const { selectedIds, toggle } = useMultiSelection();
        toggle(1);
        toggle(2);
        expect(selectedIds.value.has(1)).toBe(true);
        expect(selectedIds.value.has(2)).toBe(true);
        toggle(1);
        expect(selectedIds.value.has(1)).toBe(false);
    });

    it("creates a new Set on toggle (preserves Vue reactivity)", () => {
        const { selectedIds, toggle } = useMultiSelection();
        const before = selectedIds.value;
        toggle(1);
        expect(selectedIds.value).not.toBe(before);
    });

    it("selectAll replaces the current selection", () => {
        const { selectedIds, toggle, selectAll } = useMultiSelection();
        toggle(99);
        selectAll([1, 2, 3]);
        expect([...selectedIds.value]).toEqual([1, 2, 3]);
    });

    it("clear empties the selection and exits selecting mode", () => {
        const { selectedIds, isSelecting, toggle, clear } = useMultiSelection();
        isSelecting.value = true;
        toggle(1);
        clear();
        expect(selectedIds.value.size).toBe(0);
        expect(isSelecting.value).toBe(false);
    });
});
