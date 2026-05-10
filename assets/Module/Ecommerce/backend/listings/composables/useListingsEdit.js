import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { emptyListingForm, makeListingImageRef } from "./useListingsCreate.js";

export function useListingsEdit(updatePath, reset) {
    const { t } = useI18n();
    const showEdit = ref(false);
    const editingListing = ref(null);
    const editForm = ref(emptyListingForm());
    const editFormImage = makeListingImageRef(editForm);
    const { errors: editErrors, validate, clearErrors, setErrors } = useForm();
    const { loading: editLoading, request: editRequest } = useRequest();

    function openEdit(listing) {
        editingListing.value = listing;
        editForm.value = {
            productId: listing.product.id,
            slug: listing.slug,
            marketingTitle: listing.marketingTitle ?? "",
            marketingDescription: listing.marketingDescription ?? "",
            isVisibleOnShop: listing.isVisibleOnShop,
            seoTitle: listing.seoTitle ?? "",
            seoDescription: listing.seoDescription ?? "",
            featuredImageId: listing.featuredImage?.id ?? null,
            featuredImageUrl: listing.featuredImage?.url ?? null,
        };
        clearErrors();
        showEdit.value = true;
    }

    async function submitEdit() {
        if (
            !validate({
                slug: () =>
                    required(
                        t("backend.ecommerce.listings.errors.slug_required"),
                    )(editForm.value.slug),
            })
        )
            return;
        const url = buildPath(updatePath, { id: editingListing.value.id });
        const data = await editRequest(url, editForm.value);
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(t("backend.ecommerce.listings.updated"));
            reset();
        } else {
            if (data.errors?._global) toast.error(data.errors._global);
            setErrors(translateServerErrors(t, data.errors));
        }
    }

    return {
        showEdit,
        editingListing,
        editForm,
        editFormImage,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
