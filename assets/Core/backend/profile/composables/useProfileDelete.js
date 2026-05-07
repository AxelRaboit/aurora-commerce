import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";

export function useProfileDelete(deletePath, loginPath, deleteCsrf) {
    const { t } = useI18n();

    const deleteLoading = ref(false);

    async function deleteAccount() {
        if (!confirm(t("backend.profile.danger.confirm"))) return;
        deleteLoading.value = true;
        try {
            const response = await fetch(deletePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ _token: deleteCsrf }),
            });
            const data = await response.json();
            if (data.success) {
                window.location.href = loginPath;
            }
        } finally {
            deleteLoading.value = false;
        }
    }

    return { deleteLoading, deleteAccount };
}
