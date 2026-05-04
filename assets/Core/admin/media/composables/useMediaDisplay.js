import { ref, computed } from "vue";

export const TYPE_FILTERS = [
    { key: "all", label: "admin.media.filterAll" },
    { key: "image", label: "admin.media.filterImages" },
    { key: "video", label: "admin.media.filterVideos" },
    { key: "application/pdf", label: "admin.media.filterPdf" },
    { key: "other", label: "admin.media.filterOther" },
];

export function useMediaDisplay(media) {
    const viewMode = ref(localStorage.getItem("aurora-media-view") ?? "grid");
    function setViewMode(mode) {
        viewMode.value = mode;
        localStorage.setItem("aurora-media-view", mode);
    }

    const typeFilter = ref("all");
    const sortBy = ref(localStorage.getItem("aurora-media-sort") ?? "position");
    const sortDir = ref(localStorage.getItem("aurora-media-sort-dir") ?? "asc");

    function setSort(field) {
        if (sortBy.value === field)
            sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
        else {
            sortBy.value = field;
            sortDir.value = "asc";
        }
        localStorage.setItem("aurora-media-sort", sortBy.value);
        localStorage.setItem("aurora-media-sort-dir", sortDir.value);
    }

    const displayedMedia = computed(() => {
        let list = [...media.value];
        if (typeFilter.value !== "all") {
            list = list.filter((m) => {
                if (typeFilter.value === "image")
                    return m.mimeType.startsWith("image/");
                if (typeFilter.value === "video")
                    return m.mimeType.startsWith("video/");
                if (typeFilter.value === "application/pdf")
                    return m.mimeType === "application/pdf";
                return (
                    !m.mimeType.startsWith("image/") &&
                    !m.mimeType.startsWith("video/") &&
                    m.mimeType !== "application/pdf"
                );
            });
        }
        const dir = sortDir.value === "asc" ? 1 : -1;
        list.sort((a, b) => {
            if (sortBy.value === "name")
                return dir * a.originalName.localeCompare(b.originalName);
            if (sortBy.value === "size") return dir * (a.size - b.size);
            if (sortBy.value === "date")
                return (
                    dir *
                    (new Date(a.createdAt ?? 0) - new Date(b.createdAt ?? 0))
                );
            return dir * (a.position - b.position);
        });
        return list;
    });

    const reorderEnabled = computed(() => sortBy.value === "position");

    return {
        viewMode,
        setViewMode,
        typeFilter,
        TYPE_FILTERS,
        sortBy,
        sortDir,
        setSort,
        displayedMedia,
        reorderEnabled,
    };
}
