import { computed } from "vue";
import { useListViewMode } from "@/shared/composables/list/useListViewMode.js";
import { useListSort } from "@/shared/composables/list/useListSort.js";

/**
 * Display state for /backend/ged/documents:
 *   - view mode toggle (grid ↔ list, persisted)
 *   - sort by name | size | date (persisted, asc/desc toggle on re-click)
 *
 * Pass the raw items list reactively; consumers get a `displayedItems`
 * computed they iterate directly. Sorting is purely client-side — the
 * pagination already came from the server.
 */
export const DOCUMENT_SORT_FIELDS = [
    { key: "date", labelKey: "shared.common.dates" },
    { key: "name", label: "A-Z" },
    { key: "size", label: "KB" },
];

export function useDocumentsDisplay(items) {
    const { viewMode, setViewMode } = useListViewMode(
        "aurora-ged-view",
        "list",
    );
    const { sortBy, sortDir, setSort } = useListSort(
        "aurora-ged-sort",
        "aurora-ged-sort-dir",
        "date",
        "desc",
    );

    const displayedItems = computed(() => {
        const dir = sortDir.value === "asc" ? 1 : -1;
        const list = [...items.value];
        list.sort((a, b) => {
            if (sortBy.value === "name") {
                return dir * (a.title ?? "").localeCompare(b.title ?? "");
            }
            if (sortBy.value === "size") {
                return dir * ((a.fileSize ?? 0) - (b.fileSize ?? 0));
            }
            return (
                dir * (new Date(a.createdAt ?? 0) - new Date(b.createdAt ?? 0))
            );
        });
        return list;
    });

    return {
        viewMode,
        setViewMode,
        sortBy,
        sortDir,
        setSort,
        displayedItems,
    };
}
