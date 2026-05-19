import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
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
        loading: createLoading,
        submit: submitCreate,
        clearErrors,
    } = useFormAction({
        rules: () => ({
            title: () =>
                required(t("backend.projects.errors.title_required"))(
                    newProject.value.title,
                ),
        }),
        url: () => createPath,
        body: () => newProject.value,
        onSuccess: () => {
            showCreate.value = false;
            toast.success(t("backend.projects.toast.created"));
            reset();
        },
    });

    function openCreate() {
        newProject.value = emptyProjectForm();
        clearErrors();
        showCreate.value = true;
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
