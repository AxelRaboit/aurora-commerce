import { ref, computed } from "vue";

export function useTagList(tagsPath, initialTags, initialSearch) {
    const parsed = (() => { try { return JSON.parse(initialTags); } catch { return { items: [], total: 0, page: 1, totalPages: 1 }; } })();

    const tags = ref(parsed.items ?? []);
    const page = ref(parsed.page ?? 1);
    const totalPages = ref(parsed.totalPages ?? 1);
    const search = ref(initialSearch ?? "");

    function addTag(tag) {
        tags.value.unshift(tag);
    }

    function updateTag(updatedTag) {
        const index = tags.value.findIndex((t) => t.id === updatedTag.id);
        if (index !== -1) tags.value[index] = updatedTag;
    }

    function removeTag(id) {
        tags.value = tags.value.filter((t) => t.id !== id);
    }

    function performSearch() {
        const url = new URL(tagsPath, window.location.origin);
        if (search.value) url.searchParams.set("search", search.value);
        window.location.href = url.toString();
    }

    function goToPage(newPage) {
        const url = new URL(tagsPath, window.location.origin);
        if (newPage > 1) url.searchParams.set("page", newPage);
        if (search.value) url.searchParams.set("search", search.value);
        window.location.href = url.toString();
    }

    return { tags, page, totalPages, search, addTag, updateTag, removeTag, performSearch, goToPage };
}
