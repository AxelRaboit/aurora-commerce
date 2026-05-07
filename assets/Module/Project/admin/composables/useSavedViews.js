import { ref, watch } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Saved-view dropdown for the active project. The "filters" payload is whatever
 * the caller passes — typically `{ statusFilter, search }` from useProjectsListPage.
 */
export function useSavedViews(paths, activeProject) {
    const { t } = useI18n();

    const views = ref([]);
    const selectedViewId = ref(null);
    const showSaveModal = ref(false);
    const newViewName = ref("");

    async function load() {
        if (!activeProject.value) {
            views.value = [];
            return;
        }
        try {
            const url = buildPath(paths.list, { id: activeProject.value.id });
            const response = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            const data = await response.json();
            if (data.success) views.value = data.views ?? [];
        } catch {
            // silent — saved views are non-critical
        }
    }

    watch(
        () => activeProject.value?.id,
        () => load(),
        { immediate: true },
    );

    async function saveView(filters) {
        if (!activeProject.value) return;
        const name = newViewName.value.trim();
        if (!name) {
            toast.error(t("backend.projects.errors.saved_view_name_required"));
            return;
        }
        const url = buildPath(paths.create, { id: activeProject.value.id });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ name, filters }),
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (!data.success) throw new Error();
            showSaveModal.value = false;
            newViewName.value = "";
            await load();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function deleteView(view) {
        const url = buildPath(paths.delete, { viewId: view.id });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
            });
            if (!response.ok) throw new Error();
            if (selectedViewId.value === view.id) selectedViewId.value = null;
            await load();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    function applyView(view, applyCallback) {
        selectedViewId.value = view.id;
        applyCallback(view.filters);
    }

    return {
        views,
        selectedViewId,
        showSaveModal,
        newViewName,
        load,
        saveView,
        deleteView,
        applyView,
    };
}
