import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { required } from "@/shared/utils/validation/validators.js";
import { emptyProjectForm } from "./useProjectsCreate.js";

export function useProjectsEdit(updatePath, reset, activeProject) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editingProject = ref(null);
    const editForm = ref(emptyProjectForm());
    const { errors: editErrors, validate, clearErrors, handleErrors } = useServerErrors();
    const { loading: editLoading, request } = useRequest();

    function openEdit(project) {
        editingProject.value = project;
        editForm.value = {
            title: project.title,
            description: project.description ?? "",
            status: project.status,
            startDate: project.startDate ?? "",
            endDate: project.endDate ?? "",
            responsibleUserId: project.responsibleUser?.id ?? "",
            crmContactIds: (project.crmContacts ?? []).map(
                (contact) => contact.id,
            ),
            crmCompanyId: project.crmCompany?.id ?? "",
            crmDealId: project.crmDeal?.id ?? "",
        };
        clearErrors();
        showEdit.value = true;
    }

    async function submitEdit() {
        if (
            !validate({
                title: () =>
                    required(t("backend.projects.errors.title_required"))(
                        editForm.value.title,
                    ),
            })
        )
            return;
        const url = buildPath(updatePath, { id: editingProject.value.id });
        const data = await request(url, editForm.value);
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(t("backend.projects.toast.updated"));
            reset();
            if (
                activeProject?.value &&
                activeProject.value.id === editingProject.value.id &&
                data.project
            ) {
                activeProject.value = data.project;
            }
        } else {
            handleErrors(data.errors);
        }
    }

    return {
        showEdit,
        editingProject,
        editForm,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
