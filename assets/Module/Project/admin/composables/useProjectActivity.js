import { ref, watch } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";

/**
 * Loads the activity timeline (audit log entries) for the active project.
 * Auto-reloads when the active project changes.
 */
export function useProjectActivity(activityPath, activeProject) {
    const entries = ref([]);
    const loading = ref(false);

    async function load() {
        if (!activeProject.value) {
            entries.value = [];
            return;
        }
        loading.value = true;
        try {
            const url = buildPath(activityPath, { id: activeProject.value.id });
            const response = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            const data = await response.json();
            if (data.success) {
                entries.value = data.entries ?? [];
            }
        } finally {
            loading.value = false;
        }
    }

    // Reload whenever the active project changes (open/close/reload).
    watch(
        () => activeProject.value?.id,
        () => load(),
        { immediate: true },
    );

    return { entries, loading, reload: load };
}
