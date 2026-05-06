import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useThemesDelete(themeList, deletePath) {
    const { t } = useI18n();
    const deletingTheme = ref(null);

    async function confirmDelete() {
        const theme = deletingTheme.value;
        if (!theme) return;
        try {
            const url = buildPath(deletePath, { id: theme.id });
            const response = await fetch(url, { method: HttpMethod.Post });
            const data = await response.json();
            if (!data.success) {
                toast.error(t(data.error ?? "shared.common.error"));
                deletingTheme.value = null;
                return;
            }
            themeList.value = themeList.value.filter(
                (item) => item.id !== theme.id,
            );
            deletingTheme.value = null;
            toast.success(t("backend.themes.deleted"));
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return { deletingTheme, confirmDelete };
}
