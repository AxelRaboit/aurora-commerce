import { ref, watchEffect } from "vue";
import { buildTree as buildHierarchicalTree } from "@/shared/composables/tree/useHierarchicalTree.js";

/**
 * Build a hierarchical tree from the flat block-notes list. Filters by
 * free-text query (title or tag substring + server-side content matches)
 * and tag-pill OR-selection. Ancestors of matching nodes are preserved.
 *
 * Mirrors `useNoteTree` from the Markdown module but kept independent so
 * each flavour can evolve filters separately (e.g. future block-type
 * filter).
 */
export function useBlockNoteTree(
    notesRef,
    queryRef = null,
    selectedTagsRef = null,
    contentMatchIdsRef = null,
) {
    const tree = ref([]);

    watchEffect(() => {
        const query = (queryRef?.value ?? "").trim().toLowerCase();
        const tags = selectedTagsRef?.value ?? [];
        const contentIds = contentMatchIdsRef?.value ?? null;
        const fullTree = buildHierarchicalTree(notesRef.value).map(decorate);
        const hasFilter = query !== "" || tags.length > 0;
        tree.value = hasFilter
            ? filterTree(fullTree, query, tags, contentIds)
            : fullTree;
    });

    function decorate(node) {
        return {
            ...node,
            matched: true,
            children: (node.children ?? []).map(decorate),
        };
    }

    function matchesQuery(node, query, contentIds) {
        if (query === "") return true;
        const title = (node.title ?? "").toLowerCase();
        if (title.includes(query)) return true;
        const nodeTags = node.tags ?? [];
        if (nodeTags.some((t) => String(t).toLowerCase().includes(query))) {
            return true;
        }
        if (contentIds && contentIds.has(node.id)) return true;
        return false;
    }

    function matchesTags(node, tags) {
        if (tags.length === 0) return true;
        const noteTags = node.tags ?? [];
        return tags.some((tag) => noteTags.includes(tag));
    }

    function filterTree(nodes, query, tags, contentIds) {
        const kept = [];
        for (const node of nodes) {
            const children = filterTree(node.children, query, tags, contentIds);
            const selfMatch =
                matchesQuery(node, query, contentIds) &&
                matchesTags(node, tags);
            if (selfMatch || children.length > 0) {
                kept.push({ ...node, matched: selfMatch, children });
            }
        }
        return kept;
    }

    return { tree };
}
