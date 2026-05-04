import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useGalleryEditReopen(reopenPath, galleryRef) {
    const { t } = useI18n();

    const showReopenModal = ref(false);
    const reopenLoading = ref(false);

    function askReopen() {
        if (!reopenPath) return;
        showReopenModal.value = true;
    }

    async function confirmReopen() {
        if (!reopenPath || reopenLoading.value) return;
        reopenLoading.value = true;
        try {
            const res = await fetch(reopenPath, { method: "POST" });
            const data = await res.json();
            if (data?.success) {
                galleryRef.value = data.gallery;
                showReopenModal.value = false;
                toast.success(t("photo.galleries.admin.reopened"));
            } else {
                toast.error(t("shared.common.error"));
            }
        } finally {
            reopenLoading.value = false;
        }
    }

    return { showReopenModal, reopenLoading, askReopen, confirmReopen };
}
