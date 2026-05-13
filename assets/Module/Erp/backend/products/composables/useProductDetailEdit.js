import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { required } from "@/shared/utils/validation/validators.js";
import { DEFAULT_CURRENCY } from "@/shared/utils/format/currencies.js";
import { makeImageRef } from "./useProductsOptions.js";

export function useProductDetailEdit(updatePath, product) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editForm = ref({
        name: product.value.name,
        reference: product.value.reference ?? "",
        description: product.value.description ?? "",
        price: product.value.price ?? "",
        currency: product.value.currency ?? DEFAULT_CURRENCY,
        status: product.value.status ?? "draft",
        type: product.value.type ?? "physical",
    });
    const editFormImage = makeImageRef(editForm);

    const {
        errors: editErrors,
        loading: editLoading,
        submit: submitEdit,
    } = useFormAction({
        rules: () => ({
            name: () =>
                required(t("backend.erp.products.errors.name_required"))(
                    editForm.value.name,
                ),
        }),
        url: () => updatePath,
        body: () => ({
            name: editForm.value.name,
            reference: editForm.value.reference,
            description: editForm.value.description,
            price: editForm.value.price === "" ? null : editForm.value.price,
            currency: editForm.value.currency,
            status: editForm.value.status,
            type: editForm.value.type,
        }),
        onSuccess: (data) => {
            product.value = {
                ...product.value,
                ...(data.product ?? editForm.value),
            };
            showEdit.value = false;
            toast.success(t("shared.common.saved"));
        },
    });

    return {
        showEdit,
        editForm,
        editFormImage,
        editErrors,
        editLoading,
        submitEdit,
    };
}

export function useProductActivityLabels() {
    const { t } = useI18n();
    return (action) => {
        const map = {
            "product.created": t("backend.erp.activity.created"),
            "product.updated": t("backend.erp.activity.updated"),
            "product.deleted": t("backend.erp.activity.deleted"),
        };
        return map[action] ?? action;
    };
}
