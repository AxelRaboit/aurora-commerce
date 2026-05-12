import { ref, computed } from "vue";
import {
    buildFolderTree,
    flattenFolders,
} from "@/shared/utils/tree/folderTree.js";

export function useDocumentFolderTree(items) {
    const collapsedIds = ref(new Set());
    const search = ref("");

    function toggleCollapse(id) {
        const next = new Set(collapsedIds.value);
        if (next.has(id)) {
            next.delete(id);
        } else {
            next.add(id);
        }
        collapsedIds.value = next;
    }

    const flatTree = computed(() =>
        flattenFolders(
            buildFolderTree(
                items.value,
                (a, b) =>
                    a.position - b.position || a.name.localeCompare(b.name),
            ),
            0,
            collapsedIds.value,
        ),
    );

    const filteredTree = computed(() => {
        const q = search.value.trim().toLowerCase();
        if (!q) return flatTree.value;
        return flatTree.value.filter((node) =>
            node.name.toLowerCase().includes(q),
        );
    });

    return { flatTree, filteredTree, search, collapsedIds, toggleCollapse };
}
