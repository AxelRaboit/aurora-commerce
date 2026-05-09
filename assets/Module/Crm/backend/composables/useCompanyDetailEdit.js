import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required, url } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

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
    const { errors: editErrors, validate, clearErrors, setErrors } = useForm();
    const { loading: editLoading, request } = useApiRequest();

    async function submitEdit() {
        if (
            !validate({
                name: () =>
                    required(t("backend.crm.companies.errors.name_required"))(
                        editForm.value.name,
                    ),
                website: () =>
                    url(t("backend.crm.companies.errors.website_invalid"))(
                        editForm.value.website,
                    ),
            })
        )
            return;
        const data = await request(updatePath, editForm.value);
        if (!data) return;
        if (data.success) {
            company.value = {
                ...company.value,
                ...(data.company ?? editForm.value),
            };
            showEdit.value = false;
            toast.success(t("shared.common.saved"));
        } else {
            setErrors(translateServerErrors(t, data.errors));
        }
    }

    return { showEdit, editForm, editErrors, editLoading, submitEdit };
}
