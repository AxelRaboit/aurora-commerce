import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { required } from "@/shared/utils/validation/validators.js";
import {
    emptyProductForm,
    buildProductPayload,
    makeImageRef,
} from "./useProductsOptions.js";

export function useProductsEdit(updatePath, reset) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editingProduct = ref(null);
    const editForm = ref(emptyProductForm());
    const editFormImage = makeImageRef(editForm);

    const {
        errors: editErrors,
        loading: editLoading,
        submit: submitEdit,
        clearErrors,
    } = useFormAction({
        rules: () => ({
            name: () =>
                required(t("backend.erp.products.errors.name_required"))(
                    editForm.value.name,
                ),
        }),
        url: () => buildPath(updatePath, { id: editingProduct.value.id }),
        body: () => buildProductPayload(editForm.value),
        onSuccess: () => {
            showEdit.value = false;
            toast.success(t("backend.erp.products.updated"));
            reset();
        },
    });

    function openEdit(product) {
        editingProduct.value = product;
        editForm.value = {
            name: product.name,
            reference: product.reference,
            description: product.description ?? "",
            price: product.price ?? "",
            currency: product.currency ?? "EUR",
            status: product.status ?? "draft",
            type: product.type ?? "physical",
            imageId: product.image?.id ?? null,
            imageUrl: product.image?.url ?? null,
            stockQuantity: product.stockQuantity ?? "",
        };
        clearErrors();
        showEdit.value = true;
    }

    return {
        showEdit,
        editingProduct,
        editForm,
        editFormImage,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
