import { ref, computed } from "vue";
import {
    buildFolderTree,
    flattenFolders,
} from "@core/backend/media/utils/folderTree.js";

export function useMediaFolderTree(folders, currentFolderId) {
    const folderTree = computed(() => buildFolderTree(folders.value));

    const collapsedFolderIds = ref(loadCollapsedFolderIds());
    const favouriteFolderIds = ref(loadFavouriteFolderIds());

    function loadFavouriteFolderIds() {
        try {
            const raw = localStorage.getItem("aurora-media-favourite-folders");
            return raw ? new Set(JSON.parse(raw)) : new Set();
        } catch {
            return new Set();
        }
    }

    function loadCollapsedFolderIds() {
        try {
            const raw = localStorage.getItem("aurora-media-collapsed-folders");
            return raw ? new Set(JSON.parse(raw)) : new Set();
        } catch {
            return new Set();
        }
    }

    function toggleFavourite(folderId) {
        const s = new Set(favouriteFolderIds.value);
        if (s.has(folderId)) s.delete(folderId);
        else s.add(folderId);
        favouriteFolderIds.value = s;
        try {
            localStorage.setItem(
                "aurora-media-favourite-folders",
                JSON.stringify([...s]),
            );
        } catch {}
    }

    function toggleCollapse(folderId) {
        if (collapsedFolderIds.value.has(folderId))
            collapsedFolderIds.value.delete(folderId);
        else collapsedFolderIds.value.add(folderId);
        collapsedFolderIds.value = new Set(collapsedFolderIds.value);
        try {
            localStorage.setItem(
                "aurora-media-collapsed-folders",
                JSON.stringify([...collapsedFolderIds.value]),
            );
        } catch {}
    }

    const flatFolders = computed(() =>
        flattenFolders(folderTree.value, 0, collapsedFolderIds.value),
    );
    const allFlatFolders = computed(() => flattenFolders(folderTree.value));

    const favouriteFolders = computed(() =>
        folders.value.filter((f) => favouriteFolderIds.value.has(f.id)),
    );

    const currentFolder = computed(
        () => folders.value.find((f) => f.id === currentFolderId.value) ?? null,
    );

    const breadcrumbs = computed(() => {
        const chain = [];
        let current = currentFolder.value;
        while (current) {
            chain.unshift(current);
            current =
                folders.value.find((f) => f.id === current.parentId) ?? null;
        }
        return chain;
    });

    const childFolders = computed(() =>
        folders.value
            .filter(
                (f) => (f.parentId ?? null) === (currentFolderId.value ?? null),
            )
            .sort((a, b) => a.name.localeCompare(b.name)),
    );

    return {
        folderTree,
        flatFolders,
        allFlatFolders,
        currentFolder,
        breadcrumbs,
        childFolders,
        collapsedFolderIds,
        toggleCollapse,
        favouriteFolderIds,
        toggleFavourite,
        favouriteFolders,
    };
}
