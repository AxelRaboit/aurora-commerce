import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
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
        validate: validateEdit,
        handleErrors: handleEditErrors,
    } = useServerErrors();
    const { loading: editLoading, request: editRequest } = useRequest();

    async function submitEdit() {
        if (
            !validateEdit({
                name: () =>
                    required(t("backend.erp.products.errors.name_required"))(
                        editForm.value.name,
                    ),
            })
        )
            return;

        const data = await editRequest(updatePath, {
            name: editForm.value.name,
            reference: editForm.value.reference,
            description: editForm.value.description,
            price: editForm.value.price === "" ? null : editForm.value.price,
            currency: editForm.value.currency,
            status: editForm.value.status,
            type: editForm.value.type,
        });
        if (!data) return;
        if (data.success) {
            product.value = {
                ...product.value,
                ...(data.product ?? editForm.value),
            };
            showEdit.value = false;
            toast.success(t("shared.common.saved"));
        } else {
            handleEditErrors(data.errors);
        }
    }

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
