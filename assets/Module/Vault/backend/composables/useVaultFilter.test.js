import { describe, expect, it, vi, beforeEach } from "vitest";
import { ref } from "vue";
import { useVaultFilter } from "@vault/backend/composables/useVaultFilter.js";

vi.mock("vue-i18n", () => ({
    useI18n: () => ({ t: (key) => key }),
}));

function makeNav({
    allView = true,
    allFoldersView = false,
    showFavorites = false,
    currentFolderId = null,
} = {}) {
    return {
        allView: ref(allView),
        allFoldersView: ref(allFoldersView),
        showFavorites: ref(showFavorites),
        currentFolderId: ref(currentFolderId),
    };
}

const baseEntries = [
    {
        id: 1,
        title: "Gmail",
        url: "https://gmail.com",
        folderId: null,
        isFavorite: true,
    },
    {
        id: 2,
        title: "GitHub",
        url: "https://github.com",
        folderId: 1,
        isFavorite: false,
    },
    { id: 3, title: "Stripe", url: null, folderId: null, isFavorite: false },
];

const baseFolders = [{ id: 1, name: "Work", parentId: null }];

describe("useVaultFilter", () => {
    let entries;
    let folders;

    beforeEach(() => {
        entries = ref([...baseEntries]);
        folders = ref([...baseFolders]);
    });

    it("returns all entries when allView is true and no search", () => {
        const nav = makeNav({ allView: true });
        const { filteredEntries } = useVaultFilter(entries, folders, nav);

        expect(filteredEntries.value).toHaveLength(3);
    });

    it("returns only favorites when showFavorites is true", () => {
        const nav = makeNav({ allView: false, showFavorites: true });
        const { filteredEntries } = useVaultFilter(entries, folders, nav);

        expect(filteredEntries.value).toHaveLength(1);
        expect(filteredEntries.value[0].id).toBe(1);
    });

    it("returns entries in the current folder and its descendants", () => {
        const nav = makeNav({
            allView: false,
            showFavorites: false,
            currentFolderId: 1,
        });
        const { filteredEntries } = useVaultFilter(entries, folders, nav);

        expect(filteredEntries.value).toHaveLength(1);
        expect(filteredEntries.value[0].id).toBe(2);
    });

    it("filters entries by title when searchQuery matches", () => {
        const nav = makeNav({ allView: true });
        const { searchQuery, filteredEntries } = useVaultFilter(
            entries,
            folders,
            nav,
        );
        searchQuery.value = "git";

        expect(filteredEntries.value).toHaveLength(1);
        expect(filteredEntries.value[0].id).toBe(2);
    });

    it("filters entries by url when searchQuery matches", () => {
        const nav = makeNav({ allView: true });
        const { searchQuery, filteredEntries } = useVaultFilter(
            entries,
            folders,
            nav,
        );
        searchQuery.value = "gmail.com";

        expect(filteredEntries.value).toHaveLength(1);
        expect(filteredEntries.value[0].id).toBe(1);
    });

    it("returns empty filteredFolders when no search query", () => {
        const nav = makeNav({ allView: true });
        const { filteredFolders } = useVaultFilter(entries, folders, nav);

        expect(filteredFolders.value).toHaveLength(0);
    });

    it("returns matching folders when search query matches a folder name", () => {
        const nav = makeNav({ allView: true });
        const { searchQuery, filteredFolders } = useVaultFilter(
            entries,
            folders,
            nav,
        );
        searchQuery.value = "work";

        expect(filteredFolders.value).toHaveLength(1);
        expect(filteredFolders.value[0].id).toBe(1);
    });

    it("emptyMessage returns favorites key when showFavorites is true", () => {
        const nav = makeNav({ allView: false, showFavorites: true });
        const { emptyMessage } = useVaultFilter(entries, folders, nav);

        expect(emptyMessage.value).toBe("vault.entries.emptyFavorites");
    });

    it("emptyMessage returns folder key when viewing a specific folder", () => {
        const nav = makeNav({
            allView: false,
            showFavorites: false,
            currentFolderId: 1,
        });
        const { emptyMessage } = useVaultFilter(entries, folders, nav);

        expect(emptyMessage.value).toBe("vault.entries.emptyFolder");
    });

    it("emptyMessage returns general empty key for allView", () => {
        const nav = makeNav({ allView: true });
        const { emptyMessage } = useVaultFilter(entries, folders, nav);

        expect(emptyMessage.value).toBe("vault.entries.empty");
    });
});
