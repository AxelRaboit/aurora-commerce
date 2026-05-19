import { ref, computed } from "vue";

export function useVaultFolderAccordion(entries, folders) {
    const expandedFolderIds = ref(new Set());

    function toggleFolderExpanded(folderId) {
        const next = new Set(expandedFolderIds.value);
        if (next.has(folderId)) next.delete(folderId);
        else next.add(folderId);
        expandedFolderIds.value = next;
    }

    const rootFolders = computed(() =>
        folders.value
            .filter((f) => !f.parentId)
            .sort(
                (a, b) =>
                    a.position - b.position || a.name.localeCompare(b.name),
            ),
    );

    const folderEntryCounts = computed(() => {
        const counts = {};
        entries.value.forEach((entry) => {
            if (entry.folderId)
                counts[entry.folderId] = (counts[entry.folderId] ?? 0) + 1;
        });
        return counts;
    });

    const folderChildCounts = computed(() => {
        const counts = {};
        folders.value.forEach((folder) => {
            if (folder.parentId)
                counts[folder.parentId] = (counts[folder.parentId] ?? 0) + 1;
        });
        return counts;
    });

    function entriesInFolder(folderId) {
        return entries.value.filter((entry) => entry.folderId === folderId);
    }

    return {
        expandedFolderIds,
        toggleFolderExpanded,
        rootFolders,
        folderEntryCounts,
        folderChildCounts,
        entriesInFolder,
    };
}
