import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { emptyProjectForm } from "./useProjectsCreate.js";

export function useProjectsEdit(updatePath, reset, activeProject) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editingProject = ref(null);
    const editForm = ref(emptyProjectForm());
    const { errors: editErrors, validate, clearErrors, setErrors } = useForm();
    const { loading: editLoading, request } = useApiRequest();

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
            setErrors(translateServerErrors(t, data.errors));
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
