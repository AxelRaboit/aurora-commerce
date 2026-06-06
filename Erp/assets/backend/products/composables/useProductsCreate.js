import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { required } from "@/shared/utils/validation/validators.js";
import {
    emptyProductForm,
    buildProductPayload,
    makeImageRef,
} from "./useProductsOptions.js";

export function useProductsCreate(createPath, reset) {
    const { t } = useI18n();

    const showCreate = ref(false);
    const newProduct = ref(emptyProductForm());
    const newProductImage = makeImageRef(newProduct);

    const {
        errors: createErrors,
        loading: createLoading,
        submit: submitCreate,
        clearErrors,
    } = useFormAction({
        rules: () => ({
            name: () =>
                required(t("backend.erp.products.errors.name_required"))(
                    newProduct.value.name,
                ),
        }),
        url: () => createPath,
        body: () => buildProductPayload(newProduct.value),
        onSuccess: () => {
            showCreate.value = false;
            toast.success(t("backend.erp.products.created"));
            reset();
        },
    });

    function openCreate() {
        newProduct.value = emptyProductForm();
        clearErrors();
        showCreate.value = true;
    }

    return {
        showCreate,
        newProduct,
        newProductImage,
        createErrors,
        createLoading,
        openCreate,
        submitCreate,
    };
}
