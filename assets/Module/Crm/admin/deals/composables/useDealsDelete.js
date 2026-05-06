import { useDelete } from "@/shared/composables/form/useDelete.js";

export function useDealsDelete(
    deletePath,
    reset,
    kanbanColumnsLoaded,
    ensureKanbanColumns,
) {
    const {
        pendingDelete,
        loading: deleteLoading,
        confirm: confirmDelete,
        submit: doDelete,
    } = useDelete(
        deletePath,
        () => {
            reset();
            if (kanbanColumnsLoaded.value) ensureKanbanColumns(true);
        },
        "backend.crm.deals.deleted",
    );

    return { pendingDelete, deleteLoading, confirmDelete, doDelete };
}
