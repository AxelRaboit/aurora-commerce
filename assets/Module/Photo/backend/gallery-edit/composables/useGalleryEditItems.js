import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { openMediaPicker } from "@/shared/utils/mediaPicker.js";

export function useGalleryEditItems(props, initialItems) {
    const { t } = useI18n();

    const items = ref([...initialItems]);
    const selected = ref(new Set());

    const allSelected = computed(
        () =>
            items.value.length > 0 &&
            selected.value.size === items.value.length,
    );

    function toggleSelect(itemId) {
        if (selected.value.has(itemId)) selected.value.delete(itemId);
        else selected.value.add(itemId);
        selected.value = new Set(selected.value);
    }

    function toggleSelectAll() {
        if (allSelected.value) selected.value = new Set();
        else selected.value = new Set(items.value.map((i) => i.id));
    }

    function itemById(itemId) {
        return items.value.find((i) => i.id === itemId);
    }

    function itemPreview(itemId) {
        return items.value.find((i) => i.id === itemId)?.thumb ?? null;
    }

    // ── Add photos ────────────────────────────────────────────────────────────
    async function addPhotos() {
        const picked = await openMediaPicker({
            imagesOnly: true,
            multiple: true,
        });
        if (!Array.isArray(picked) || picked.length === 0) return;
        try {
            const response = await fetch(props.itemsAddPath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ mediaIds: picked.map((m) => m.id) }),
            });
            const data = await response.json();
            if (!data?.success) {
                toast.error(t("shared.common.error"));
                return;
            }
            items.value = data.items ?? items.value;
            toast.success(
                t("photo.galleries.itemsAdded", { count: data.added }),
            );
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    // ── Single delete ─────────────────────────────────────────────────────────
    const pendingDeleteItem = ref(null);
    const deleteOneLoading = ref(false);

    function askDeleteOne(item) {
        pendingDeleteItem.value = item;
    }

    async function confirmDeleteOne() {
        if (!pendingDeleteItem.value || deleteOneLoading.value) return;
        deleteOneLoading.value = true;
        const item = pendingDeleteItem.value;
        try {
            const response = await fetch(
                buildPath(props.itemsDeletePath, { id: item.id }),
                { method: HttpMethod.Post },
            );
            const data = await response.json();
            if (data?.success) {
                items.value = items.value.filter((i) => i.id !== item.id);
                selected.value.delete(item.id);
                selected.value = new Set(selected.value);
                pendingDeleteItem.value = null;
                toast.success(t("photo.galleries.itemsDeleted", { count: 1 }));
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            deleteOneLoading.value = false;
        }
    }

    // ── Bulk delete ───────────────────────────────────────────────────────────
    const pendingBulkDelete = ref(false);
    const bulkDeleteLoading = ref(false);

    function askBulkDelete() {
        if (selected.value.size === 0) return;
        pendingBulkDelete.value = true;
    }

    async function confirmBulkDelete() {
        if (bulkDeleteLoading.value) return;
        const ids = Array.from(selected.value);
        if (ids.length === 0) {
            pendingBulkDelete.value = false;
            return;
        }
        bulkDeleteLoading.value = true;
        try {
            const response = await fetch(props.itemsBulkDeletePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ itemIds: ids }),
            });
            const data = await response.json();
            if (data?.success) {
                items.value = items.value.filter(
                    (i) => !selected.value.has(i.id),
                );
                selected.value = new Set();
                pendingBulkDelete.value = false;
                toast.success(
                    t("photo.galleries.itemsDeleted", { count: data.deleted }),
                );
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            bulkDeleteLoading.value = false;
        }
    }

    // ── Drag-and-drop reorder ─────────────────────────────────────────────────
    let draggingIndex = null;

    function onDragStart(index, event) {
        draggingIndex = index;
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData("text/plain", String(index));
    }

    function onDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
    }

    async function onDrop(targetIndex) {
        if (draggingIndex === null || draggingIndex === targetIndex) return;
        const reordered = [...items.value];
        const [moved] = reordered.splice(draggingIndex, 1);
        reordered.splice(targetIndex, 0, moved);
        items.value = reordered;
        draggingIndex = null;
        try {
            await fetch(props.itemsReorderPath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ itemIds: reordered.map((i) => i.id) }),
            });
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return {
        items,
        selected,
        allSelected,
        toggleSelect,
        toggleSelectAll,
        itemById,
        itemPreview,
        addPhotos,
        pendingDeleteItem,
        deleteOneLoading,
        askDeleteOne,
        confirmDeleteOne,
        pendingBulkDelete,
        bulkDeleteLoading,
        askBulkDelete,
        confirmBulkDelete,
        onDragStart,
        onDragOver,
        onDrop,
    };
}
