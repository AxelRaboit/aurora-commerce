import { ref } from "vue";
import { parseJson } from "@/utils/parseJson.js";

/**
 * Generic paginated list composable.
 * @param {string} basePath - URL used for search/pagination navigation
 * @param {string} initialJson - JSON string with { items, page, totalPages }
 * @param {string} initialSearch
 */
export function useList(basePath, initialJson, initialSearch) {
    const parsed = parseJson(initialJson, {
        items: [],
        total: 0,
        page: 1,
        totalPages: 1,
    });

    const items = ref(parsed.items ?? []);
    const page = ref(parsed.page ?? 1);
    const totalPages = ref(parsed.totalPages ?? 1);
    const search = ref(initialSearch ?? "");

    function addItem(item) {
        items.value.unshift(item);
    }

    function updateItem(updated) {
        const index = items.value.findIndex((item) => item.id === updated.id);
        if (index !== -1) items.value[index] = updated;
    }

    function removeItem(id) {
        items.value = items.value.filter((item) => item.id !== id);
    }

    function performSearch() {
        const url = new URL(basePath, window.location.origin);
        if (search.value) url.searchParams.set("search", search.value);
        window.location.href = url.toString();
    }

    function goToPage(newPage) {
        const url = new URL(basePath, window.location.origin);
        if (newPage > 1) url.searchParams.set("page", newPage);
        if (search.value) url.searchParams.set("search", search.value);
        window.location.href = url.toString();
    }

    return {
        items,
        page,
        totalPages,
        search,
        addItem,
        updateItem,
        removeItem,
        performSearch,
        goToPage,
    };
}
