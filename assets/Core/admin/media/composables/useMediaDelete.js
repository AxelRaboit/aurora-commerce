import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useMediaDelete(props, media, editingMedia) {
    const { t } = useI18n();

    const deletingMedia = ref(null);
    const deletingMediaUsage = ref(null);
    const deletingMediaUsageLoading = ref(false);

    async function askDeleteMedia(item) {
        deletingMedia.value = item;
        deletingMediaUsage.value = null;
        deletingMediaUsageLoading.value = true;
        try {
            const response = await fetch(`/admin/media/${item.id}/usage`, {
                headers: { Accept: "application/json" },
            });
            if (response.ok) deletingMediaUsage.value = await response.json();
        } catch {
            /* surfaced as no-usage in modal */
        } finally {
            deletingMediaUsageLoading.value = false;
        }
    }

    async function confirmDeleteMedia() {
        const item = deletingMedia.value;
        if (!item) return;
        try {
            const response = await fetch(
                buildPath(props.deletePath, { id: item.id }),
                { method: HttpMethod.Post },
            );
            const data = await response.json();
            if (!data.success) {
                toast.error(t("shared.common.error"));
                return;
            }
            media.value = media.value.filter((m) => m.id !== item.id);
            toast.success(t("shared.common.deleted"));
            if (editingMedia.value?.id === item.id) editingMedia.value = null;
        } catch {
            toast.error(t("shared.common.error"));
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
