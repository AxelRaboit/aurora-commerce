import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { required } from "@/shared/utils/validation/validators.js";

export function emptyTaskForm(columnId = null) {
    return {
        title: "",
        description: "",
        columnId,
        priority: "medium",
        assigneeId: "",
        dueDate: "",
        position: 0,
        labelIds: [],
        storyPoints: "",
        estimateMinutes: "",
        sprintId: "",
        items: [],
    };
}

export function useTasksCreate(taskCreatePath, activeProject, reloadDetail) {
    const { t } = useI18n();

    const showCreateTask = ref(false);
    const newTask = ref(emptyTaskForm());
    const {
        errors: createTaskErrors,
        validate,
        clearErrors,
        handleErrors,
    } = useServerErrors();
    const { loading: createTaskLoading, request } = useRequest();

    function openCreateTask(columnId = null) {
        // Default to the first column of the active project when no explicit column was provided.
        const fallbackColumnId =
            columnId ?? activeProject.value?.columns?.[0]?.id ?? null;
        newTask.value = emptyTaskForm(fallbackColumnId);
        clearErrors();
        showCreateTask.value = true;
    }

    async function submitCreateTask() {
        if (!activeProject.value) return;
        if (
            !validate({
                title: () =>
                    required(t("backend.projects.errors.title_required"))(
                        newTask.value.title,
                    ),
            })
        )
            return;
        const url = buildPath(taskCreatePath, { id: activeProject.value.id });
        const data = await request(url, newTask.value);
        if (!data) return;
        if (data.success) {
            showCreateTask.value = false;
            toast.success(t("backend.projects.toast.taskCreated"));
            await reloadDetail();
        } else {
            handleErrors(data.errors);
        }
    }

    return {
        showCreateTask,
        newTask,
        createTaskErrors,
        createTaskLoading,
        openCreateTask,
        submitCreateTask,
    };
}
