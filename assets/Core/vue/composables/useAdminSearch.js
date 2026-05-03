import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { modKeyLabel } from "@/shared/utils/platform.js";

export function useAdminSearch({ searchPath, navItems }) {
    const searchOpen = ref(false);
    const searchQuery = ref("");
    const searchResults = ref({ posts: [], terms: [], media: [] });
    const searchLoading = ref(false);
    const searchHighlightedIndex = ref(0);
    const searchInputRef = ref(null);

    const flatResults = computed(() => [
        ...searchResults.value.posts.map((item) => ({ kind: "post", item })),
        ...searchResults.value.terms.map((item) => ({ kind: "term", item })),
        ...searchResults.value.media.map((item) => ({ kind: "media", item })),
    ]);

    const totalResults = computed(() => flatResults.value.length);

    function openPalette() {
        searchOpen.value = true;
        searchQuery.value = "";
        searchResults.value = { posts: [], terms: [], media: [] };
        searchHighlightedIndex.value = 0;
        nextTick(() => searchInputRef.value?.focus());
    }

    function closePalette() {
        searchOpen.value = false;
    }

    function entryIndex(kind, item) {
        return flatResults.value.findIndex((entry) => entry.kind === kind && entry.item.id === item.id);
    }

    function findNavPath(routePrefix) {
        return navItems.value?.find((i) => i.route.startsWith(routePrefix))?.path ?? null;
    }

    function activateResult(entry) {
        if (!entry) return;
        if ("post" === entry.kind) {
            const path = findNavPath("admin_posts");
            if (!path) return;
            const url = new URL(path, window.location.origin);
            if (entry.item.trashed) url.searchParams.set("trashed", "1");
            window.location.href = url.toString();
        } else if ("term" === entry.kind) {
            const path = findNavPath("admin_taxonomies");
            if (path) window.location.href = path;
        } else if ("media" === entry.kind) {
            const path = findNavPath("admin_media");
            if (path) window.location.href = path;
        }
    }

    function onGlobalKeydown(event) {
        if ((event.ctrlKey || event.metaKey) && "k" === event.key.toLowerCase()) {
            event.preventDefault();
            searchOpen.value ? closePalette() : openPalette();
            return;
        }
        if (!searchOpen.value) return;
        if ("Escape" === event.key) {
            event.preventDefault();
            closePalette();
        } else if ("ArrowDown" === event.key) {
            event.preventDefault();
            if (totalResults.value) searchHighlightedIndex.value = (searchHighlightedIndex.value + 1) % totalResults.value;
        } else if ("ArrowUp" === event.key) {
            event.preventDefault();
            if (totalResults.value) searchHighlightedIndex.value = (searchHighlightedIndex.value - 1 + totalResults.value) % totalResults.value;
        } else if ("Enter" === event.key) {
            event.preventDefault();
            activateResult(flatResults.value[searchHighlightedIndex.value]);
        }
    }

    async function runSearch() {
        const trimmed = searchQuery.value.trim();
        if ("" === trimmed) {
            searchResults.value = { posts: [], terms: [], media: [] };
            return;
        }
        searchLoading.value = true;
        try {
            const url = new URL(searchPath, window.location.origin);
            url.searchParams.set("q", trimmed);
            const response = await fetch(url);
            if (!response.ok) throw new Error();
            const data = await response.json();
            searchResults.value = {
                posts: data.posts ?? [],
                terms: data.terms ?? [],
                media: data.media ?? [],
            };
            searchHighlightedIndex.value = 0;
        } catch {
            searchResults.value = { posts: [], terms: [], media: [] };
        } finally {
            searchLoading.value = false;
        }
    }

    watch(searchQuery, useDebounce(runSearch, 180));

    onMounted(() => window.addEventListener("keydown", onGlobalKeydown));
    onBeforeUnmount(() => window.removeEventListener("keydown", onGlobalKeydown));

    return {
        searchOpen,
        searchQuery,
        searchResults,
        searchLoading,
        searchHighlightedIndex,
        searchInputRef,
        flatResults,
        totalResults,
        openPalette,
        closePalette,
        activateResult,
        entryIndex,
    };
}
