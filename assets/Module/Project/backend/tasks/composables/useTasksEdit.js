import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { required } from "@/shared/utils/validation/validators.js";
import { emptyTaskForm } from "./useTasksCreate.js";

export function useTasksEdit(taskUpdatePath, reloadDetail) {
    const { t } = useI18n();

    const showEditTask = ref(false);
    const showViewTask = ref(false);
    const editingTask = ref(null);
    const editTaskForm = ref(emptyTaskForm());
    const {
        errors: editTaskErrors,
        validate,
        clearErrors,
        handleErrors,
    } = useServerErrors();
    const { loading: editTaskLoading, request } = useRequest();

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

    async function submitEditTask() {
        if (
            !validate({
                title: () =>
                    required(t("backend.projects.errors.title_required"))(
                        editTaskForm.value.title,
                    ),
            })
        )
            return;
        const url = buildPath(taskUpdatePath, {
            taskId: editingTask.value.id,
        });
        const data = await request(url, editTaskForm.value);
        if (!data) return;
        if (data.success) {
            showEditTask.value = false;
            toast.success(t("backend.projects.toast.taskUpdated"));
            await reloadDetail();
        } else {
            handleErrors(data.errors);
        }
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
