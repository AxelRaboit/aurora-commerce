import { ref, watch } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useProjectDetail(showPath) {
    const { request } = useRequest();
    const activeProject = ref(null);
    const activeTasks = ref([]);
    const detailLoading = ref(false);

    // Local Kanban columns mirroring activeTasks, keyed by columnId. Used as
    // v-model for VueDraggable so drag&drop mutates the per-column arrays directly.
    const localColumns = ref({});

    function rebuildLocalColumns() {
        const next = {};
        const columns = activeProject.value?.columns ?? [];
        for (const column of columns) {
            next[column.id] = [];
        }

        for (const task of activeTasks.value) {
            if (next[task.columnId]) next[task.columnId].push(task);
        }

        for (const columnId of Object.keys(next)) {
            next[columnId].sort(
                (left, right) => (left.position ?? 0) - (right.position ?? 0),
            );
        }
        localColumns.value = next;
    }

    watch(
        [activeTasks, () => activeProject.value?.columns],
        rebuildLocalColumns,
        {
            immediate: true,
            deep: true,
        },
    );

    function tasksByColumn(columnId) {
        return localColumns.value[columnId] ?? [];
    }

    async function openProject(project) {
        detailLoading.value = true;
        try {
            const url = buildPath(showPath, { id: project.id });
            const data = await request(url, null, HttpMethod.Get);
            if (data?.success) {
                activeProject.value = data.project;
                activeTasks.value = data.tasks;
            }
        } finally {
            detailLoading.value = false;
        }
    }

    function closeDetail() {
        activeProject.value = null;
        activeTasks.value = [];
    }

    async function reloadProject() {
        if (!activeProject.value) return;
        await openProject(activeProject.value);
    }

    return {
        activeProject,
        activeTasks,
        detailLoading,
        localColumns,
        tasksByColumn,
        openProject,
        closeDetail,
        reloadProject,
    };
}
