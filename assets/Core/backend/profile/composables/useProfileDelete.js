import { useI18n } from "vue-i18n";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useProfileDelete(deletePath, loginPath, deleteCsrf) {
    const { t } = useI18n();
    const { loading: deleteLoading, request } = useRequest();

    async function deleteAccount() {
        if (!confirm(t("backend.profile.danger.confirm"))) return;
        const data = await request(deletePath, { _token: deleteCsrf });
        if (data?.success) {
            window.location.href = loginPath;
        }
    }

    return { deleteLoading, deleteAccount };
}
