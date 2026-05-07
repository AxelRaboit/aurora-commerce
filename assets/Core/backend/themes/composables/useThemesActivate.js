import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export function useThemesActivate(themeList, activatePath) {
    const { t } = useI18n();

    async function activateTheme(theme) {
        try {
            const url = buildPath(activatePath, { id: theme.id });
            const response = await fetch(url, { method: HttpMethod.Post });
            const data = await response.json();
            if (!data.success) {
                toast.error(t("shared.common.error"));
                return;
            }
            themeList.value = themeList.value.map((item) => ({
                ...item,
                active: item.id === theme.id,
            }));
            toast.success(t("backend.themes.activated"));
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return { activateTheme };
}
