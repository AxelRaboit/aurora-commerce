import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
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
        validate,
        clearErrors,
        handleErrors,
    } = useServerErrors();
    const { loading: createLoading, request } = useRequest();

    function openCreate() {
        newCompany.value = emptyCompanyForm();
        clearErrors();
        showCreate.value = true;
    }

    async function submitCreate() {
        if (
            !validate({
                name: () =>
                    required(t("backend.crm.companies.errors.name_required"))(
                        newCompany.value.name,
                    ),
                website: () =>
                    url(t("backend.crm.companies.errors.website_invalid"))(
                        newCompany.value.website,
                    ),
            })
        )
            return;
        const data = await request(createPath, newCompany.value);
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.crm.companies.created"));
            reset();
        } else handleErrors(data.errors);
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
