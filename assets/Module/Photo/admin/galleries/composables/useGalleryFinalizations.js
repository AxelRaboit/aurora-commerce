import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useLocalPagination } from "@/shared/composables/list/useLocalPagination.js";

export function useGalleryFinalizations(deletePath, finalizations, invites, galleryRef) {
    const { t } = useI18n();

    const expandedFinalizations = ref(new Set());
    const {
        page: finalizationsPage,
        totalPages: finalizationsTotalPages,
        paginatedItems: paginatedFinalizations,
        goToPage: goToFinalizationsPage,
    } = useLocalPagination(finalizations);

    function toggleFinalization(id) {
        if (expandedFinalizations.value.has(id)) expandedFinalizations.value.delete(id);
        else expandedFinalizations.value.add(id);
        expandedFinalizations.value = new Set(expandedFinalizations.value);
    }

    const pendingFinalizationDelete = ref(null);
    const finalizationDeleteLoading = ref(false);

    function askDeleteFinalization(finalization) {
        if (!deletePath) return;
        pendingFinalizationDelete.value = finalization;
    }

    async function confirmDeleteFinalization() {
        if (!pendingFinalizationDelete.value || finalizationDeleteLoading.value) return;
        finalizationDeleteLoading.value = true;
        const finalization = pendingFinalizationDelete.value;
        try {
            const res = await fetch(deletePath.replace("__id__", finalization.id), { method: "DELETE" });
            const data = await res.json();
            if (data?.success) {
                finalizations.value = data.finalizations ?? finalizations.value.filter((f) => f.id !== finalization.id);
                if (data.invites) invites.value = data.invites;
                galleryRef.value = data.gallery ?? galleryRef.value;
                pendingFinalizationDelete.value = null;
                toast.success(t("photo.galleries.admin.finalizations.reopened"));
            } else {
                toast.error(t("shared.common.error"));
            }
        } finally {
            finalizationDeleteLoading.value = false;
        }
    }

    return {
        expandedFinalizations,
        finalizationsPage,
        finalizationsTotalPages,
        paginatedFinalizations,
        goToFinalizationsPage,
        toggleFinalization,
        pendingFinalizationDelete,
        finalizationDeleteLoading,
        askDeleteFinalization,
        confirmDeleteFinalization,
    };
}
