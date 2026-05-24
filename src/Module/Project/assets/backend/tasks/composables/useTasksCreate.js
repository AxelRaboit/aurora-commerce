import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
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
        loading: createTaskLoading,
        submit: submitCreateTask,
        clearErrors,
    } = useFormAction({
        rules: () => ({
            title: () =>
                required(t("backend.projects.errors.title_required"))(
                    newTask.value.title,
                ),
        }),
        url: () => buildPath(taskCreatePath, { id: activeProject.value.id }),
        body: () => newTask.value,
        onSuccess: async () => {
            showCreateTask.value = false;
            toast.success(t("backend.projects.toast.task_created"));
            await reloadDetail();
        },
    });

    function openCreateTask(columnId = null) {
        // Default to the first column of the active project when no explicit column was provided.
        const fallbackColumnId =
            columnId ?? activeProject.value?.columns?.[0]?.id ?? null;
        newTask.value = emptyTaskForm(fallbackColumnId);
        clearErrors();
        showCreateTask.value = true;
    }

    async function submitCreateTaskGuarded() {
        if (!activeProject.value) return;
        await submitCreateTask();
    }

    return {
        showCreateTask,
        newTask,
        createTaskErrors,
        createTaskLoading,
        openCreateTask,
        submitCreateTask: submitCreateTaskGuarded,
    };
}
