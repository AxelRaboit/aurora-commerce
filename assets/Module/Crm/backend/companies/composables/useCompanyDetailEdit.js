import { ref } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { required, url } from "@/shared/utils/validation/validators.js";

export function useCompanyDetailEdit(updatePath, company) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editForm = ref({
        name: company.value.name,
        industry: company.value.industry ?? "",
        website: company.value.website ?? "",
        phone: company.value.phone ?? "",
        address: company.value.address ?? "",
        notes: company.value.notes ?? "",
    });

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
        url: () => updatePath,
        body: () => editForm.value,
        onSuccess: (data) => {
            company.value = {
                ...company.value,
                ...(data.company ?? editForm.value),
            };
            showEdit.value = false;
            toast.success(t("shared.common.saved"));
        },
    });

    return { showEdit, editForm, editErrors, editLoading, submitEdit, clearErrors };
}
