import { ref, watch } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useMediaPickerEdit({ editPath, items, selected }) {
    const { t } = useI18n();
    const { loading: editSaving, request } = useRequest();
    const editAlt = ref("");
    const editCaption = ref("");
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
        const url = buildPath(editPath, { id: item.id });
        const data = await request(url, {
            alt: editAlt.value,
            caption: editCaption.value,
            focalX: item.focalX ?? null,
            focalY: item.focalY ?? null,
            folderId: item.folderId ?? null,
        });
        if (!data || !data.success) {
            if (data !== null) toast.error(t("shared.common.error"));
            return;
        }
        const index = items.value.findIndex((m) => m.id === item.id);
        if (index !== -1) items.value[index] = data.media;
        selected.value = data.media;
        editSaved.value = true;
        if (savedTimer) clearTimeout(savedTimer);
        savedTimer = setTimeout(() => {
            editSaved.value = false;
        }, 2000);
    }

    return { editAlt, editCaption, editSaving, editSaved, saveEdit };
}
