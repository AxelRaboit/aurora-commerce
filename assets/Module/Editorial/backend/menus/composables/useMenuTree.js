import { computed } from "vue";

/**
 * Tree helpers for the menu editor panel.
 *
 * Exposes:
 *   - `itemCount(menuRef)` — total descendants of the menu (recursive).
 *   - `applyChildrenReorder(item, children)` — mutates `item.children`
 *     in place after a drag-drop on a sub-list, so the parent SFC can
 *     just emit a reorder event without owning the splice logic.
 *
 * Kept as a thin composable rather than a util so future tree-related
 * helpers (selection, expand state, etc.) have a natural home next to
 * the panel.
 */
export function useMenuTree(menuRef) {
    const itemCount = computed(() => {
        const items = menuRef.value?.items;
        if (!items) return 0;
        return countDescendants(items);
    });

    function countDescendants(items) {
        return items.reduce(
            (acc, item) =>
                acc + 1 + (item.children?.length ? countDescendants(item.children) : 0),
            0,
        );
    }

    function applyChildrenReorder(item, children) {
        item.children = children;
    }

    return { itemCount, applyChildrenReorder };
}
