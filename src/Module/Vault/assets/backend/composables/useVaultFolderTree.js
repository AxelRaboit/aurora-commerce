import { ref, computed } from "vue";
import {
    buildTree,
    flattenFolders,
    getAncestors,
} from "@vault/backend/composables/useVaultTree.js";

const LS_COLLAPSED = "aurora-vault-collapsed-folders";

export function useVaultFolderTree(folders, currentFolderId) {
    const folderTree = computed(() => buildTree(folders.value));

    const collapsedFolderIds = ref(loadCollapsed());

    function loadCollapsed() {
        try {
            const raw = localStorage.getItem(LS_COLLAPSED);
            return raw ? new Set(JSON.parse(raw)) : new Set();
        } catch {
            return new Set();
        }
    }

    function toggleCollapse(folderId) {
        const next = new Set(collapsedFolderIds.value);
        if (next.has(folderId)) next.delete(folderId);
        else next.add(folderId);
        collapsedFolderIds.value = next;
        try {
            localStorage.setItem(LS_COLLAPSED, JSON.stringify([...next]));
        } catch {} // best-effort: localStorage may be unavailable (private mode, quota)
    }

    const flatFolders = computed(() =>
        flattenFolders(folderTree.value, 0, collapsedFolderIds.value),
    );
    const allFlatFolders = computed(() => flattenFolders(folderTree.value));

    const breadcrumbs = computed(() => {
        if (!currentFolderId.value) return [];
        return getAncestors(folders.value, currentFolderId.value);
    });

    const currentFolder = computed(
        () => folders.value.find((f) => f.id === currentFolderId.value) ?? null,
    );

    return {
        folderTree,
        flatFolders,
        allFlatFolders,
        breadcrumbs,
        currentFolder,
        collapsedFolderIds,
        toggleCollapse,
    };
}
