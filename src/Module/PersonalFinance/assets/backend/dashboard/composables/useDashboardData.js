import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Owns the dashboard snapshot ref and the /refresh fetch. Initialised
 * from the SSR-rendered snapshot prop; refresh() rebuilds the snapshot
 * client-side without a full page reload (used after edits surface in
 * the KPI tiles).
 */
export function useDashboardData(refreshPath, initialSnapshot) {
    const { t } = useI18n();
    const snapshot = ref(initialSnapshot);
    const loading = ref(false);

    async function refresh() {
        if (loading.value) return;
        loading.value = true;
        try {
            const response = await fetch(refreshPath, {
                method: HttpMethod.Get,
                headers: { Accept: "application/json" },
            });
            const payload = await response.json().catch(() => ({}));
            if (response.ok && payload?.success !== false && payload.snapshot) {
                snapshot.value = payload.snapshot;
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            loading.value = false;
        }
    }

    return { snapshot, loading, refresh };
}
