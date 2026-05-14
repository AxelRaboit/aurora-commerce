import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { required } from "@/shared/utils/validation/validators.js";

/**
 * CRUD + reorder for the project's Kanban columns.
 *
 * `paths` carries the four route templates (create/update/delete/reorder).
 * The composable manages the create modal, the rename modal, and the delete confirm modal.
 */
export function useColumnsManage(paths, activeProject, reloadDetail) {
    const { t } = useI18n();

    // ── Create new column ────────────────────────────────────────────────────
    const showCreateColumn = ref(false);
    const newColumn = ref({ label: "" });
    const {
        errors: createColumnErrors,
        validate: validateCreate,
        clearErrors: clearCreateErrors,
        handleErrors: handleCreateErrors,
    } = useServerErrors();
    const { loading: createColumnLoading, request: createRequest } =
        useRequest();

    function openCreateColumn() {
        newColumn.value = { label: "" };
        clearCreateErrors();
        showCreateColumn.value = true;
    }

    async function submitCreateColumn() {
        if (!activeProject.value) return;
        if (
            !validateCreate({
                label: () =>
                    required(
                        t("backend.projects.errors.column_label_required"),
                    )(newColumn.value.label),
            })
        )
            return;
        const url = buildPath(paths.create, { id: activeProject.value.id });
        const data = await createRequest(url, newColumn.value);
        if (!data) return;
        if (data.success) {
            showCreateColumn.value = false;
            await reloadDetail();
        } else {
            handleCreateErrors(data.errors);
        }
    }

    // ── Rename column ────────────────────────────────────────────────────────
    const showRenameColumn = ref(false);
    const editingColumn = ref(null);
    const renameForm = ref({ label: "" });
    const {
        errors: renameErrors,
        validate: validateRename,
        clearErrors: clearRenameErrors,
        handleErrors: handleRenameErrors,
    } = useServerErrors();
    const { loading: renameLoading, request: renameRequest } = useRequest();
    const { request: deleteRequest } = useRequest();

    function openRenameColumn(column) {
        editingColumn.value = column;
        renameForm.value = { label: column.label };
        clearRenameErrors();
        showRenameColumn.value = true;
    }

    async function submitRenameColumn() {
        if (!editingColumn.value) return;
        if (
            !validateRename({
                label: () =>
                    required(
                        t("backend.projects.errors.column_label_required"),
                    )(renameForm.value.label),
            })
        )
            return;
        const url = buildPath(paths.update, {
            columnId: editingColumn.value.id,
        });
        const data = await renameRequest(url, renameForm.value);
        if (!data) return;
        if (data.success) {
            showRenameColumn.value = false;
            await reloadDetail();
        } else {
            handleRenameErrors(data.errors);
        }
    }

    // ── Delete column ────────────────────────────────────────────────────────
    const pendingDeleteColumn = ref(null);
    const deleteColumnLoading = ref(false);

    function confirmDeleteColumn(column) {
        pendingDeleteColumn.value = column;
    }

    async function doDeleteColumn() {
        if (!pendingDeleteColumn.value) return;
        deleteColumnLoading.value = true;
        const url = buildPath(paths.delete, {
            columnId: pendingDeleteColumn.value.id,
        });
        const data = await deleteRequest(url);
        deleteColumnLoading.value = false;
        if (!data) return;
        if (data.success) {
            pendingDeleteColumn.value = null;
            await reloadDetail();
        } else {
            toast.error(
                translateServerErrors(t, data.errors)._global ??
                    t("shared.common.error"),
            );
        }
    }

    // ── Reorder columns (drag&drop on the columns themselves) ────────────────
    // Local mutable copy of the project's column list — bound as v-model to the
    // outer VueDraggable so the user can drop columns into a new order.
    const orderedColumns = ref([]);

    watch(
        () => activeProject.value?.columns,
        (next) => {
            orderedColumns.value = [...(next ?? [])];
        },
        { immediate: true, deep: true },
    );

    async function persistColumnsOrder() {
        if (!activeProject.value) return;
        const orderedIds = orderedColumns.value.map((column) => column.id);
        const url = buildPath(paths.reorder, { id: activeProject.value.id });
        await deleteRequest(url, { orderedIds }, { noGuard: true });
    }

    return {
        showCreateColumn,
        newColumn,
        createColumnErrors,
        createColumnLoading,
        openCreateColumn,
        submitCreateColumn,

        showRenameColumn,
        editingColumn,
        renameForm,
        renameErrors,
        renameLoading,
        openRenameColumn,
        submitRenameColumn,

        pendingDeleteColumn,
        deleteColumnLoading,
        confirmDeleteColumn,
        doDeleteColumn,

        orderedColumns,
        persistColumnsOrder,
    };
}
