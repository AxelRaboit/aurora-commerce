import { describe, expect, it, vi, beforeEach, afterEach } from "vitest";
import { nextTick } from "vue";
import { useClientFilteredList } from "./useClientFilteredList.js";

const items = [
    { id: 1, label: "Alpha", slug: "alpha" },
    { id: 2, label: "Beta", slug: "beta" },
    { id: 3, label: "Gamma", slug: "gamma" },
];

const matcher = (item, query) =>
    item.label.toLowerCase().includes(query) || item.slug.toLowerCase().includes(query);

describe("useClientFilteredList", () => {
    beforeEach(() => {
        global.fetch = vi.fn();
    });

    afterEach(() => {
        delete global.fetch;
    });

    it("returns all items when search is empty", () => {
        const { filteredItems } = useClientFilteredList(items, "/list", matcher);
        expect(filteredItems.value).toHaveLength(3);
    });

    it("filters by matcher predicate (case-insensitive trimmed)", async () => {
        const { searchInput, filteredItems } = useClientFilteredList(items, "/list", matcher);
        searchInput.value = "  BET  ";
        await nextTick();
        expect(filteredItems.value).toEqual([{ id: 2, label: "Beta", slug: "beta" }]);
    });

    it("reload() refetches from listPath and replaces items", async () => {
        global.fetch.mockResolvedValueOnce({
            json: async () => ({ items: [{ id: 99, label: "Reloaded", slug: "reloaded" }] }),
        });
        const { filteredItems, reload } = useClientFilteredList(items, "/list", matcher);
        await reload();
        expect(filteredItems.value).toHaveLength(1);
        expect(filteredItems.value[0].id).toBe(99);
    });

    it("reload() defaults to empty array when payload has no items key", async () => {
        global.fetch.mockResolvedValueOnce({ json: async () => ({}) });
        const { items: itemsRef, reload } = useClientFilteredList(items, "/list", matcher);
        await reload();
        expect(itemsRef.value).toEqual([]);
    });

    it("reload() is a no-op when listPath is null (consumer manages items externally)", async () => {
        const { items: itemsRef, reload } = useClientFilteredList(items, null, matcher);
        await reload();
        expect(global.fetch).not.toHaveBeenCalled();
        expect(itemsRef.value).toHaveLength(3);
    });

    it("does not mutate the initial array reference", () => {
        const seed = [...items];
        const { items: itemsRef } = useClientFilteredList(seed, "/list", matcher);
        itemsRef.value.pop();
        expect(seed).toHaveLength(3);
    });
});
