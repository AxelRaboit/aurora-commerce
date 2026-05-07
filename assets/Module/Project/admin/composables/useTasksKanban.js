import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useTasksKanban(taskReorderPath, activeProject, localColumns) {
    const { t } = useI18n();

    async function persistColumnOrder(columnId) {
        if (!activeProject.value) return;
        const orderedIds = (localColumns.value[columnId] ?? []).map(
            (task) => task.id,
        );
        // Reflect new column on local task objects so re-renders stay consistent.
        for (const task of localColumns.value[columnId] ?? []) {
            if (task.columnId !== columnId) task.columnId = columnId;
        }
        const url = buildPath(taskReorderPath, { id: activeProject.value.id });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ columnId, orderedIds }),
            });
            if (!response.ok) throw new Error();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    function onColumnEnd(columnId) {
        persistColumnOrder(columnId);
    }

    function onColumnAdd(columnId) {
        // A task was dropped into this column from another column.
        persistColumnOrder(columnId);
    }

    return {
        persistColumnOrder,
        onColumnEnd,
        onColumnAdd,
    };
}
