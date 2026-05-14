import { ref, onMounted, onUnmounted } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

function mediaUrl(base, folderId, search, all = false) {
    const url = new URL(base, window.location.origin);
    if (all) url.searchParams.set("all", "1");
    else if (folderId) url.searchParams.set("folderId", String(folderId));
    if (search) url.searchParams.set("search", search);
    return url;
}

export function useMediaNavigation(props, clearSelection) {
    const { request } = useRequest();

    const media = ref([...props.media]);
    const folders = ref([...props.folders]);
    const currentFolderId = ref(props.currentFolderId);
    const allMediaView = ref(false);
    const searchQuery = ref(props.search ?? "");
    const mediaLoading = ref(false);
    let navAbort = null;

    const initialFocusId =
        Number(new URLSearchParams(globalThis.location.search).get("focus")) ||
        null;

    async function loadMedia(folderId, search, { all = false } = {}) {
        navAbort?.abort();
        navAbort = new AbortController();
        mediaLoading.value = true;
        try {
            const data = await request(
                mediaUrl(props.listPath, folderId, search, all).toString(),
                null,
                {
                    method: HttpMethod.Get,
                    signal: navAbort.signal,
                    noGuard: true,
                },
            );
            if (!data) return;
            media.value = data.items ?? [];
            folders.value = data.folders ?? folders.value;
            currentFolderId.value = folderId ?? null;
            allMediaView.value = all;
            searchQuery.value = search;
            clearSelection();
        } finally {
            mediaLoading.value = false;
        }
    }

    async function navigateTo(folderId, search = searchQuery.value) {
        await loadMedia(folderId, search);
        history.pushState(
            { folderId, search, all: false },
            "",
            mediaUrl("/backend/media", folderId, search),
        );
    }

    async function navigateToAll(search = searchQuery.value) {
        await loadMedia(null, search, { all: true });
        history.pushState(
            { folderId: null, search, all: true },
            "",
            mediaUrl("/backend/media", null, search, true),
        );
    }

    async function onPopState(event) {
        await loadMedia(
            event.state?.folderId ?? null,
            event.state?.search ?? "",
            { all: !!event.state?.all },
        );
    }

    async function focusMediaFromQuery(openEditMedia) {
        if (!initialFocusId) return;
        const data = await request(
            `/backend/media/${initialFocusId}/info`,
            null,
            { method: "GET", noGuard: true },
        );
        const item = data?.media;
        if (!item) return;
        openEditMedia(item);
        if ((item.folderId ?? null) !== currentFolderId.value) {
            currentFolderId.value = item.folderId ?? null;
            try {
                await loadMedia(currentFolderId.value, searchQuery.value);
            } catch {
                /* ignore */
            }
        }
    }

    onMounted(() => {
        history.replaceState(
            {
                folderId: currentFolderId.value,
                search: searchQuery.value,
                all: false,
            },
            "",
            mediaUrl(
                "/backend/media",
                currentFolderId.value,
                searchQuery.value,
            ),
        );
        window.addEventListener("popstate", onPopState);
    });

    onUnmounted(() => {
        window.removeEventListener("popstate", onPopState);
    });

    return {
        media,
        folders,
        currentFolderId,
        allMediaView,
        searchQuery,
        mediaLoading,
        loadMedia,
        navigateTo,
        navigateToAll,
        focusMediaFromQuery,
    };
}
