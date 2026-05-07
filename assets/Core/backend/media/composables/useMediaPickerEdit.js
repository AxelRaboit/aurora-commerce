import { ref, watch } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useMediaPickerEdit({ editPath, items, selected }) {
    const { t } = useI18n();
    const editAlt = ref("");
    const editCaption = ref("");
    const editSaving = ref(false);
    const editSaved = ref(false);
    let savedTimer = null;

    watch(selected, (item) => {
        editAlt.value = item?.alt ?? "";
        editCaption.value = item?.caption ?? "";
        editSaved.value = false;
    });

    async function saveEdit() {
        const item = selected.value;
        if (!item) return;
        if (
            editAlt.value === (item.alt ?? "") &&
            editCaption.value === (item.caption ?? "")
        )
            return;
        editSaving.value = true;
        try {
            const url = buildPath(editPath, { id: item.id });
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    alt: editAlt.value,
                    caption: editCaption.value,
                    focalX: item.focalX ?? null,
                    focalY: item.focalY ?? null,
                    folderId: item.folderId ?? null,
                }),
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (!data.success) throw new Error();
            const index = items.value.findIndex((m) => m.id === item.id);
            if (index !== -1) items.value[index] = data.media;
            selected.value = data.media;
            editSaved.value = true;
            if (savedTimer) clearTimeout(savedTimer);
            savedTimer = setTimeout(() => {
                editSaved.value = false;
            }, 2000);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            editSaving.value = false;
        }
    }

    return { editAlt, editCaption, editSaving, editSaved, saveEdit };
}
