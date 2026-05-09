import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { getDescendantIds } from "@vault/backend/composables/useVaultTree.js";

export function useVaultFilter(entries, folders, nav) {
    const { t } = useI18n();
    const searchQuery = ref("");

    const filteredEntries = computed(() => {
        let result = entries.value;

        if (nav.showFavorites.value) {
            result = result.filter((entry) => entry.isFavorite);
        } else if (
            !nav.allView.value &&
            !nav.allFoldersView.value &&
            nav.currentFolderId.value !== null
        ) {
            const folderIds = new Set(
                getDescendantIds(folders.value, nav.currentFolderId.value),
            );
            result = result.filter(
                (entry) =>
                    entry.folderId !== null && folderIds.has(entry.folderId),
            );
        }

        if (searchQuery.value.trim()) {
            const query = searchQuery.value.toLowerCase();
            result = result.filter(
                (entry) =>
                    entry.title.toLowerCase().includes(query) ||
                    entry.url?.toLowerCase().includes(query),
            );
        }

        return result;
    });

    const filteredFolders = computed(() => {
        const query = searchQuery.value.trim().toLowerCase();
        if (!query) return [];
        return folders.value.filter((folder) =>
            folder.name.toLowerCase().includes(query),
        );
    });

    const emptyMessage = computed(() => {
        if (nav.showFavorites.value) return t("vault.entries.emptyFavorites");
        if (
            !nav.allView.value &&
            !nav.allFoldersView.value &&
            nav.currentFolderId.value !== null
        )
            return t("vault.entries.emptyFolder");
        return t("vault.entries.empty");
    });

    return { searchQuery, filteredEntries, filteredFolders, emptyMessage };
}
