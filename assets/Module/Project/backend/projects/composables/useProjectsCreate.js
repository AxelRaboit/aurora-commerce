import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import { required } from "@/shared/utils/validation/validators.js";

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
        crmDealId: "",
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
        handleErrors,
    } = useServerErrors();
    const { loading: createLoading, request } = useRequest();

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
            handleErrors(data.errors);
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
