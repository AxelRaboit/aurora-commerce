import { ref } from "vue";

export function useVaultNavigation() {
    const currentFolderId = ref(null);
    const showFavorites = ref(false);
    const allView = ref(false);
    const allFoldersView = ref(true);

    function navigate(folderId) {
        currentFolderId.value = folderId;
        showFavorites.value = false;
        allView.value = false;
        allFoldersView.value = false;
    }

    function showAllEntries() {
        currentFolderId.value = null;
        showFavorites.value = false;
        allView.value = true;
        allFoldersView.value = false;
    }

    function showAllFolders() {
        currentFolderId.value = null;
        showFavorites.value = false;
        allView.value = false;
        allFoldersView.value = true;
    }

    function toggleFavorites() {
        showFavorites.value = !showFavorites.value;
        if (showFavorites.value) {
            currentFolderId.value = null;
            allView.value = false;
            allFoldersView.value = false;
        }
    }

    return {
        currentFolderId,
        showFavorites,
        allView,
        allFoldersView,
        navigate,
        showAllEntries,
        showAllFolders,
        toggleFavorites,
    };
}
