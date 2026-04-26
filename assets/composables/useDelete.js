import { HttpMethod } from "@/utils/httpMethod.js";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

/**
 * Generic delete composable with confirmation flow.
 * @param {string} deletePath - URL with __id__ placeholder
 * @param {(id: number) => void} onSuccess
 * @param {string} successMessageKey - i18n key for the success toast
 */
export function useDelete(deletePath, onSuccess, successMessageKey) {
    const { t } = useI18n();
    const pendingDelete = ref(null);
    const loading = ref(false);

    function confirm(item) {
        pendingDelete.value = item;
    }

    async function submit() {
        if (loading.value || !pendingDelete.value) return;
        loading.value = true;
        try {
            const url = deletePath.replace("__id__", pendingDelete.value.id);
            const response = await fetch(url, { method: HttpMethod.Post });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data.success) {
                const id = pendingDelete.value.id;
                pendingDelete.value = null;
                toast.success(t(successMessageKey));
                onSuccess(id);
            }
        } catch {
            toast.error(t("common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { pendingDelete, loading, confirm, submit };
}
