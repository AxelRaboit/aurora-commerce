import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useMediaDelete(props, media, editingMedia) {
    const { t } = useI18n();
    const { request } = useRequest();

    const deletingMedia = ref(null);
    const deletingMediaUsage = ref(null);
    const deletingMediaUsageLoading = ref(false);

    async function askDeleteMedia(item) {
        deletingMedia.value = item;
        deletingMediaUsage.value = null;
        deletingMediaUsageLoading.value = true;
        const data = await request(
            `/backend/media/media/${item.id}/usage`,
            null,
            {
                method: "GET",
                noGuard: true,
            },
        );
        if (data) deletingMediaUsage.value = data;
        deletingMediaUsageLoading.value = false;
    }

    async function confirmDeleteMedia() {
        const item = deletingMedia.value;
        if (!item) return;
        try {
            const data = await request(
                buildPath(props.deletePath, { id: item.id }),
            );
            if (!data) return;
            if (!data.success) {
                toast.error(t("shared.common.error"));
                return;
            }
            media.value = media.value.filter((m) => m.id !== item.id);
            toast.success(t("shared.common.deleted"));
            if (editingMedia.value?.id === item.id) editingMedia.value = null;
        } finally {
            deletingMedia.value = null;
        }
    }

    return {
        deletingMedia,
        deletingMediaUsage,
        deletingMediaUsageLoading,
        askDeleteMedia,
        confirmDeleteMedia,
    };
}
