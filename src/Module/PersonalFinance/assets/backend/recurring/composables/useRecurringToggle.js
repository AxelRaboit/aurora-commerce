import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

/**
 * Flip a recurring rule's `active` flag. When activating a rule whose
 * dayOfMonth already passed this month, the backend Manager.toggle()
 * generates the matching transaction immediately, so onToggled receives
 * the up-to-date rule (with bumped lastGeneratedAt).
 */
export function useRecurringToggle(togglePath, onToggled) {
    const { t } = useI18n();
    const loading = ref(false);

    async function toggle(rec) {
        if (loading.value) return;
        loading.value = true;
        try {
            const url = buildPath(togglePath, { id: rec.id });
            const response = await fetch(url, { method: HttpMethod.Post });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false || !payload.recurring) {
                toast.error(t("shared.common.error"));
                return;
            }
            onToggled?.(payload.recurring);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { loading, toggle };
}
