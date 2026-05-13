import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { required, url } from "@/shared/utils/validation/validators.js";
import { emptyCompanyForm } from "./useCompaniesCreate.js";

export function useCompaniesEdit(updatePath, reset) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editingCompany = ref(null);
    const editForm = ref(emptyCompanyForm());

    const { errors: editErrors, loading: editLoading, submit: submitEdit, clearErrors } = useFormAction({
        rules: () => ({
            name: () =>
                required(t("backend.crm.companies.errors.name_required"))(
                    editForm.value.name,
                ),
            website: () =>
                url(t("backend.crm.companies.errors.website_invalid"))(
                    editForm.value.website,
                ),
        }),
        url: () => buildPath(updatePath, { id: editingCompany.value.id }),
        body: () => editForm.value,
        onSuccess: () => {
            showEdit.value = false;
            toast.success(t("backend.crm.companies.updated"));
            reset();
        },
    });

    function openEdit(company) {
        editingCompany.value = company;
        editForm.value = {
            name: company.name,
            industry: company.industry ?? "",
            website: company.website ?? "",
            phone: company.phone ?? "",
            address: company.address ?? "",
            notes: company.notes ?? "",
        };
        clearErrors();
        showEdit.value = true;
    }

    return {
        showEdit,
        editingCompany,
        editForm,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
