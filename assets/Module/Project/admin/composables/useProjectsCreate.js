import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

export function emptyProjectForm() {
    return {
        title: "",
        description: "",
        status: "draft",
        startDate: "",
        endDate: "",
        responsibleUserId: "",
        crmContactIds: [],
        crmCompanyId: "",
    };
}

export function useProjectsCreate(createPath, reset) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const newProject = ref(emptyProjectForm());
    const {
        errors: createErrors,
        validate,
        clearErrors,
        setErrors,
    } = useForm();
    const { loading: createLoading, request } = useApiRequest();

    function openCreate() {
        newProject.value = emptyProjectForm();
        clearErrors();
        showCreate.value = true;
    }

    async function submitCreate() {
        if (
            !validate({
                title: () =>
                    required(t("backend.projects.errors.title_required"))(
                        newProject.value.title,
                    ),
            })
        )
            return;
        const data = await request(createPath, newProject.value);
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.projects.toast.created"));
            reset();
        } else {
            setErrors(translateServerErrors(t, data.errors));
        }
    }

    return {
        showCreate,
        newProject,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
    };
}
