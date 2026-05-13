import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { required } from "@/shared/utils/validation/validators.js";
import { emptyTaskForm } from "./useTasksCreate.js";

export function useTasksEdit(taskUpdatePath, reloadDetail) {
    const { t } = useI18n();

    const showEditTask = ref(false);
    const showViewTask = ref(false);
    const editingTask = ref(null);
    const editTaskForm = ref(emptyTaskForm());

    const { errors: editTaskErrors, loading: editTaskLoading, submit: submitEditTask, clearErrors } = useFormAction({
        rules: () => ({
            title: () =>
                required(t("backend.projects.errors.title_required"))(
                    editTaskForm.value.title,
                ),
        }),
        url: () => buildPath(taskUpdatePath, { taskId: editingTask.value.id }),
        body: () => editTaskForm.value,
        onSuccess: async () => {
            showEditTask.value = false;
            toast.success(t("backend.projects.toast.taskUpdated"));
            await reloadDetail();
        },
    });

    function openViewTask(task) {
        editingTask.value = task;
        clearErrors();
        showViewTask.value = true;
    }

    function openEditTask(task) {
        editingTask.value = task;
        editTaskForm.value = {
            title: task.title,
            description: task.description ?? "",
            columnId: task.columnId,
            priority: task.priority,
            assigneeId: task.assignee?.id ?? "",
            dueDate: task.dueDate ?? "",
            position: task.position,
            labelIds: [...(task.labelIds ?? [])],
            storyPoints: task.storyPoints ?? "",
            estimateMinutes: task.estimateMinutes ?? "",
            items: (task.items ?? []).map((item) => ({ ...item })),
        };
        clearErrors();
        showEditTask.value = true;
    }

    return {
        showEditTask,
        showViewTask,
        editingTask,
        editTaskForm,
        editTaskErrors,
        editTaskLoading,
        openViewTask,
        openEditTask,
        submitEditTask,
    };
}
