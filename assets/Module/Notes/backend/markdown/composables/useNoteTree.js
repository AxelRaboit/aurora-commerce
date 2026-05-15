import { ref, watchEffect } from 'vue';

/**
 * Build a hierarchical tree from a flat list of notes (sorted by position).
 * Each node looks like { id, parentId, title, ..., children: [...] }.
 *
 * Notes whose parent is missing (deleted before flush) bubble up to root.
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
 */
export function useNoteTree(notesRef, queryRef = null) {
    const tree = ref([]);

    watchEffect(() => {
        const query = (queryRef?.value ?? '').trim().toLowerCase();
        const fullTree = buildTree(notesRef.value);
        tree.value = query === '' ? markAllMatched(fullTree) : filterTree(fullTree, query);
    });

    function buildTree(flat) {
        const byId = new Map();
        const roots = [];

        for (const note of flat) {
            byId.set(note.id, { ...note, children: [], matched: false });
        }

        for (const node of byId.values()) {
            const parent = node.parentId != null ? byId.get(node.parentId) : null;
            if (parent) {
                parent.children.push(node);
            } else {
                roots.push(node);
            }
        }

        const byPosition = (a, b) => (a.position ?? 0) - (b.position ?? 0);
        roots.sort(byPosition);
        for (const node of byId.values()) {
            node.children.sort(byPosition);
        }

        return roots;
    }

    function markAllMatched(nodes) {
        return nodes.map((n) => ({ ...n, matched: true, children: markAllMatched(n.children) }));
    }

    function filterTree(nodes, query) {
        const kept = [];
        for (const node of nodes) {
            const children = filterTree(node.children, query);
            const selfMatch = (node.title ?? '').toLowerCase().includes(query);
            if (selfMatch || children.length > 0) {
                kept.push({ ...node, matched: selfMatch, children });
            }
        }
        return kept;
    }

    return { tree };
}
