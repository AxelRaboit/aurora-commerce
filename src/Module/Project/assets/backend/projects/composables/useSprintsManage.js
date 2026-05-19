import { ref, computed } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { required } from "@/shared/utils/validation/validators.js";

export function useSprintsManage(paths, activeProject, reloadDetail) {
    const { t } = useI18n();

    const showSprintsModal = ref(false);
    const editingSprint = ref(null);
    const pendingDeleteSprint = ref(null);
    const sprintForm = ref({
        name: "",
        startDate: "",
        endDate: "",
        isActive: false,
    });

    const {
        errors: sprintErrors,
        validate,
        clearErrors,
        handleErrors,
    } = useServerErrors();
    const { loading, request } = useRequest();

    function openSprintsModal() {
        editingSprint.value = null;
        sprintForm.value = {
            name: "",
            startDate: "",
            endDate: "",
            isActive: false,
        };
        clearErrors();
        showSprintsModal.value = true;
    }

    function startEdit(sprint) {
        editingSprint.value = sprint;
        sprintForm.value = {
            name: sprint.name,
            startDate: sprint.startDate ?? "",
            endDate: sprint.endDate ?? "",
            isActive: !!sprint.isActive,
        };
        clearErrors();
    }

    function cancelEdit() {
        editingSprint.value = null;
        sprintForm.value = {
            name: "",
            startDate: "",
            endDate: "",
            isActive: false,
        };
        clearErrors();
    }

    async function submitSprint() {
        if (!activeProject.value) return;
        if (
            !validate({
                name: () =>
                    required(t("backend.projects.errors.sprint_name_required"))(
                        sprintForm.value.name,
                    ),
            })
        )
            return;

        const url = editingSprint.value
            ? buildPath(paths.update, { sprintId: editingSprint.value.id })
            : buildPath(paths.create, { id: activeProject.value.id });
        const data = await request(url, sprintForm.value);
        if (!data) return;
        if (data.success) {
            cancelEdit();
            await reloadDetail();
        } else {
            handleErrors(data.errors);
        }
    }

    function confirmDeleteSprint(sprint) {
        pendingDeleteSprint.value = sprint;
    }

    async function deleteSprint() {
        const sprint = pendingDeleteSprint.value;
        if (!sprint) return;
        pendingDeleteSprint.value = null;
        const url = buildPath(paths.delete, { sprintId: sprint.id });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
            });
            if (!response.ok) throw new Error();
            await reloadDetail();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    const sprintOptions = computed(() =>
        (activeProject.value?.sprints ?? []).map((sprint) => ({
            value: sprint.id,
            label: sprint.name,
        })),
    );

    return {
        showSprintsModal,
        editingSprint,
        pendingDeleteSprint,
        sprintForm,
        sprintErrors,
        sprintOptions,
        loading,
        openSprintsModal,
        startEdit,
        cancelEdit,
        submitSprint,
        confirmDeleteSprint,
        deleteSprint,
    };
}
