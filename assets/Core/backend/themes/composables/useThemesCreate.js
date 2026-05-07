import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useThemesCreate(themeList, createPath) {
    const { t } = useI18n();
    const createModal = reactive({ open: false, saving: false, errors: {} });
    const createForm = reactive({ name: "", slug: "", description: "" });

    function openCreate() {
        createModal.errors = {};
        createForm.name = "";
        createForm.slug = "";
        createForm.description = "";
        createModal.open = true;
    }

    async function submitCreate() {
        createModal.saving = true;
        createModal.errors = {};
        try {
            const response = await fetch(createPath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(createForm),
            });
            const data = await response.json();
            if (!data.success) {
                createModal.errors = data.errors ?? {};
                return;
            }
            themeList.value.push(data.theme);
            createModal.open = false;
            toast.success(t("backend.themes.created"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            createModal.saving = false;
        }
    }

    return { createModal, createForm, openCreate, submitCreate };
}
