import { ref, watch } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Saved-view dropdown for the active project. The "filters" payload is whatever
 * the caller passes — typically `{ statusFilter, search }` from useProjectsListPage.
 */
export function useSavedViews(paths, activeProject) {
    const { t } = useI18n();
    const { request } = useRequest();

    const views = ref([]);
    const selectedViewId = ref(null);
    const showSaveModal = ref(false);
    const newViewName = ref("");
    const { errors: viewErrors, validate, clearErrors } = useForm();

    async function load() {
        if (!activeProject.value) {
            views.value = [];
            return;
        }
        const url = buildPath(paths.list, { id: activeProject.value.id });
        const data = await request(url, null, {
            method: HttpMethod.Get,
            noGuard: true,
        });
        if (data?.success) views.value = data.views ?? [];
    }

    watch(
        () => activeProject.value?.id,
        () => load(),
        { immediate: true },
    );

    async function saveView(filters) {
        if (!activeProject.value) return;
        if (
            !validate({
                name: () =>
                    required(
                        t("backend.projects.errors.saved_view_name_required"),
                    )(newViewName.value),
            })
        )
            return;
        const name = newViewName.value.trim();
        const url = buildPath(paths.create, { id: activeProject.value.id });
        const data = await request(url, { name, filters });
        if (!data) return;
        if (!data.success) {
            toast.error(t("shared.common.error"));
            return;
        }
        showSaveModal.value = false;
        newViewName.value = "";
        clearErrors();
        await load();
    }

    async function deleteView(view) {
        const url = buildPath(paths.delete, { viewId: view.id });
        const data = await request(url);
        if (!data) return;
        if (selectedViewId.value === view.id) selectedViewId.value = null;
        await load();
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
        viewErrors,
        load,
        saveView,
        deleteView,
        applyView,
    };
}
