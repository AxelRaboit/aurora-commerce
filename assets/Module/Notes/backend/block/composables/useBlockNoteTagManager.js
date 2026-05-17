import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

/**
 * State machine for the `BlockTagManagerModal`. Same shape as the
 * Markdown variant, with `notes.block.tags.manage.*` i18n keys.
 */
export function useBlockNoteTagManager({ api, show, onChanged }) {
    const { t } = useI18n();

    const tags = ref([]);
    const loading = ref(false);
    const query = ref("");
    const selected = ref(new Set());
    const renaming = ref(null);
    const pendingDelete = ref(null);
    const mergeTarget = ref("");
    const submitting = ref(false);

    const filteredTags = computed(() => {
        const q = query.value.trim().toLowerCase();
        if (q === "") return tags.value;
        return tags.value.filter((entry) =>
            entry.tag.toLowerCase().includes(q),
        );
    });

    const selectedTags = computed(() => Array.from(selected.value));

    function resetState() {
        query.value = "";
        selected.value = new Set();
        renaming.value = null;
        pendingDelete.value = null;
        mergeTarget.value = "";
    }

    async function refresh() {
        loading.value = true;
        try {
            const data = await api.list();
            if (!data || data.success === false) {
                tags.value = [];
                return;
            }
            tags.value = data.tags ?? [];
        } finally {
            loading.value = false;
        }
    }

    watch(show, (isOpen) => {
        if (!isOpen) return;
        resetState();
        refresh();
    });

    function toggleSelected(tag) {
        const next = new Set(selected.value);
        if (next.has(tag)) {
            next.delete(tag);
        } else {
            next.add(tag);
        }
        selected.value = next;
    }

    function isSelected(tag) {
        return selected.value.has(tag);
    }

    function beginRename(entry) {
        renaming.value = { source: entry.tag, draft: entry.tag };
        pendingDelete.value = null;
    }

    function cancelRename() {
        renaming.value = null;
    }

    async function confirmRename() {
        if (!renaming.value) return;
        const { source, draft } = renaming.value;
        const newTag = draft.trim();
        if (newTag === "" || newTag === source) {
            renaming.value = null;
            return;
        }

        submitting.value = true;
        try {
            const data = await api.rename(source, newTag);
            if (!data || data.success === false) {
                toast.error(t("notes.block.tags.manage.errors.rename_failed"));
                return;
            }
            toast.success(
                t(
                    "notes.block.tags.manage.rename_success",
                    { count: data.affected ?? 0 },
                    data.affected ?? 0,
                ),
            );
            renaming.value = null;
            onChanged();
            await refresh();
        } finally {
            submitting.value = false;
        }
    }

    function beginDelete(entry) {
        pendingDelete.value = entry.tag;
        renaming.value = null;
    }

    function cancelDelete() {
        pendingDelete.value = null;
    }

    async function confirmDelete() {
        if (!pendingDelete.value) return;
        const tag = pendingDelete.value;

        submitting.value = true;
        try {
            const data = await api.remove(tag);
            if (!data || data.success === false) {
                toast.error(t("notes.block.tags.manage.errors.delete_failed"));
                return;
            }
            toast.success(
                t(
                    "notes.block.tags.manage.delete_success",
                    { count: data.affected ?? 0 },
                    data.affected ?? 0,
                ),
            );
            pendingDelete.value = null;
            const next = new Set(selected.value);
            next.delete(tag);
            selected.value = next;
            onChanged();
            await refresh();
        } finally {
            submitting.value = false;
        }
    }

    async function confirmMerge() {
        const sources = selectedTags.value;
        const target = mergeTarget.value.trim();
        if (sources.length < 2 || target === "") return;

        submitting.value = true;
        try {
            const data = await api.merge(sources, target);
            if (!data || data.success === false) {
                toast.error(t("notes.block.tags.manage.errors.merge_failed"));
                return;
            }
            toast.success(
                t(
                    "notes.block.tags.manage.merge_success",
                    { count: data.affected ?? 0 },
                    data.affected ?? 0,
                ),
            );
            selected.value = new Set();
            mergeTarget.value = "";
            onChanged();
            await refresh();
        } finally {
            submitting.value = false;
        }
    }

    return {
        tags,
        loading,
        query,
        renaming,
        pendingDelete,
        mergeTarget,
        submitting,
        filteredTags,
        selectedTags,
        toggleSelected,
        isSelected,
        beginRename,
        cancelRename,
        confirmRename,
        beginDelete,
        cancelDelete,
        confirmDelete,
        confirmMerge,
    };
}
