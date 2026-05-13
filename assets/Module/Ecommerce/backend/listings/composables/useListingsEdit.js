import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormAction } from "@/shared/composables/form/useFormAction.js";
import { required } from "@/shared/utils/validation/validators.js";
import { emptyListingForm, makeListingImageRef } from "./useListingsCreate.js";

export function useListingsEdit(updatePath, reset) {
    const { t } = useI18n();
    const showEdit = ref(false);
    const editingListing = ref(null);
    const editForm = ref(emptyListingForm());
    const editFormImage = makeListingImageRef(editForm);

    const { errors: editErrors, loading: editLoading, submit: submitEdit, clearErrors } = useFormAction({
        rules: () => ({
            slug: () =>
                required(
                    t("backend.ecommerce.listings.errors.slug_required"),
                )(editForm.value.slug),
        }),
        url: () => buildPath(updatePath, { id: editingListing.value.id }),
        body: () => editForm.value,
        onSuccess: () => {
            showEdit.value = false;
            toast.success(t("backend.ecommerce.listings.updated"));
            reset();
        },
    });

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
