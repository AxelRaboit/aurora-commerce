import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useGalleryEditCaption(itemsCaptionPath, items) {
    const { t } = useI18n();

    const editingCaptionId = ref(null);
    const editingCaptionDraft = ref("");

    function startCaption(item) {
        editingCaptionId.value = item.id;
        editingCaptionDraft.value = item.caption ?? "";
    }

    function cancelCaption() {
        editingCaptionId.value = null;
        editingCaptionDraft.value = "";
    }

    async function saveCaption(item) {
        try {
            const response = await fetch(
                buildPath(itemsCaptionPath, { id: item.id }),
                {
                    method: HttpMethod.Post,
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        caption: editingCaptionDraft.value,
                    }),
                },
            );
            const data = await response.json();
            if (data?.success) {
                const target = items.value.find((i) => i.id === item.id);
                if (target) target.caption = editingCaptionDraft.value || null;
                cancelCaption();
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return {
        editingCaptionId,
        editingCaptionDraft,
        startCaption,
        cancelCaption,
        saveCaption,
    };
}
