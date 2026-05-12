import { ref, computed } from "vue";
import {
    buildFolderTree,
    flattenFolders,
} from "@/shared/utils/tree/folderTree.js";

export function useDocumentFolderTree(items) {
    const collapsedIds = ref(new Set());

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
        flattenFolders(buildFolderTree(items.value), 0, collapsedIds.value),
    );

    return { flatTree, collapsedIds, toggleCollapse };
}
