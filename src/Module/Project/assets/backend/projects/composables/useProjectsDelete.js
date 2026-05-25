import { useDelete } from "@/shared/composables/form/useDelete.js";

/**
 * Wraps the project + task delete flows.
 * - Project delete: closes the detail panel if the deleted project is open, then reloads the list.
 * - Task delete: useDelete expects the {id} placeholder, but taskDeletePath uses {taskId} —
 *   normalize once so both can share the same composable.
 */
export function useProjectsDelete({
    projectDeletePath,
    taskDeletePath,
    activeProject,
    activeTasks,
    closeDetail,
    reloadProjects,
}) {
    const project = useDelete(
        projectDeletePath,
        (deletedId) => {
            if (activeProject.value?.id === deletedId) closeDetail();
            reloadProjects();
        },
        "backend.projects.toast.deleted",
    );

    const normalizedTaskDeletePath = taskDeletePath.replaceAll(
        "__taskId__",
        "__id__",
    );

    const task = useDelete(
        normalizedTaskDeletePath,
        (deletedId) => {
            activeTasks.value = activeTasks.value.filter(
                (task) => task.id !== deletedId,
            );
        },
        "backend.projects.toast.task_deleted",
    );

    return {
        pendingDeleteProject: project.pendingDelete,
        projectDeleting: project.loading,
        confirmDeleteProject: project.confirm,
        doDeleteProject: project.submit,

        pendingDeleteTask: task.pendingDelete,
        taskDeleting: task.loading,
        confirmDeleteTask: task.confirm,
        doDeleteTask: task.submit,
    };
}
