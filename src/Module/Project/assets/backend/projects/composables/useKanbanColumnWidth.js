import { ref } from "vue";

const WIDTHS = [288, 352, 448];
const DEFAULT_WIDTH = 352; // M
const STORAGE_KEY = "aurora-kanban-col-width";

function load() {
    try {
        const stored = parseInt(localStorage.getItem(STORAGE_KEY) ?? "", 10);
        return WIDTHS.includes(stored) ? stored : DEFAULT_WIDTH;
    } catch {
        return DEFAULT_WIDTH;
    }
}

function persist(value) {
    try {
        localStorage.setItem(STORAGE_KEY, String(value));
    } catch {} // best-effort: localStorage may be unavailable (private mode, quota)
}

export function useKanbanColumnWidth() {
    const colWidth = ref(load());

    function setColWidth(width) {
        colWidth.value = width;
        persist(width);
    }

    return { colWidth, setColWidth, COLUMN_WIDTHS: WIDTHS };
}
