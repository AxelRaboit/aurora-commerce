import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { required, url } from "@/shared/utils/validation/validators.js";

export function emptyCompanyForm() {
    return {
        name: "",
        industry: "",
        website: "",
        phone: "",
        address: "",
        notes: "",
    };
}

export function useCompaniesCreate(createPath, reset) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const newCompany = ref(emptyCompanyForm());

    const {
        errors: createErrors,
        loading: createLoading,
        submit: submitCreate,
        clearErrors,
    } = useFormAction({
        rules: () => ({
            name: () =>
                required(t("backend.crm.companies.errors.name_required"))(
                    newCompany.value.name,
                ),
            website: () =>
                url(t("backend.crm.companies.errors.website_invalid"))(
                    newCompany.value.website,
                ),
        }),
        url: () => createPath,
        body: () => newCompany.value,
        onSuccess: () => {
            showCreate.value = false;
            toast.success(t("backend.crm.companies.created"));
            reset();
        },
    });

    function openCreate() {
        newCompany.value = emptyCompanyForm();
        clearErrors();
        showCreate.value = true;
    }

    return {
        showCreate,
        newCompany,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
    };
}
