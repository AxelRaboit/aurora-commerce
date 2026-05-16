import { ref, watchEffect } from "vue";
import { buildTree as buildHierarchicalTree } from "@/shared/composables/tree/useHierarchicalTree.js";

/**
 * Build a hierarchical tree from a flat list of notes (sorted by position).
 * Each node looks like { id, parentId, title, ..., children: [...] }.
 *
 * Exposes a *writable* `tree` ref so VueDraggable can mutate the children
 * arrays directly. A watcher rebuilds the tree whenever `notesRef` or
 * `queryRef` change — that way `refreshList()` from the server still
 * wins over any local DnD mutation.
 *
 * If `queryRef` is given and non-empty, the tree is filtered to keep
 * only nodes whose title matches (case-insensitive substring) plus the
 * ancestors needed to keep matching leaves attached. Each kept node
 * carries a `matched` flag for styling.
 *
 * Uses the shared `buildTree` helper for the base hierarchy so the
 * Notes module follows the same convention as taxonomies / listing
 * categories.
 */
export function useNoteTree(notesRef, queryRef = null, selectedTagsRef = null) {
    const tree = ref([]);

    watchEffect(() => {
        const query = (queryRef?.value ?? "").trim().toLowerCase();
        const tags = selectedTagsRef?.value ?? [];
        // The shared helper expects parentId on each item — already provided
        // by MarkdownNoteRepository.findFlatListForUser.
        const fullTree = buildHierarchicalTree(notesRef.value).map(decorate);
        const hasFilter = query !== "" || tags.length > 0;
        tree.value = hasFilter ? filterTree(fullTree, query, tags) : fullTree;
    });

    /** Annotate every node with `matched: true` for consistent template logic. */
    function decorate(node) {
        return {
            ...node,
            matched: true,
            children: (node.children ?? []).map(decorate),
        };
    }

    function matchesTags(node, tags) {
        if (tags.length === 0) return true;
        const noteTags = node.tags ?? [];
        // OR semantics: keep a note as soon as it carries any selected tag.
        return tags.some((tag) => noteTags.includes(tag));
    }

    function filterTree(nodes, query, tags) {
        const kept = [];
        for (const node of nodes) {
            const children = filterTree(node.children, query, tags);
            const titleMatch =
                query === "" ||
                (node.title ?? "").toLowerCase().includes(query);
            const selfMatch = titleMatch && matchesTags(node, tags);
            if (selfMatch || children.length > 0) {
                kept.push({ ...node, matched: selfMatch, children });
            }
        }
        return kept;
    }

    return { tree };
}
