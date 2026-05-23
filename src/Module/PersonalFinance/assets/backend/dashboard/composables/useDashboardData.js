import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Owns the dashboard snapshot ref and the /refresh fetch. Initialised
 * from the SSR-rendered snapshot prop; refresh() rebuilds the snapshot
 * client-side without a full page reload (used after edits surface in
 * the KPI tiles).
 */
export function useDashboardData(refreshPath, initialSnapshot) {
    const snapshot = ref(initialSnapshot);
    const { loading, request } = useRequest();

    async function refresh() {
        const payload = await request(refreshPath, null, HttpMethod.Get);
        if (payload && payload.success !== false && payload.snapshot) {
            snapshot.value = payload.snapshot;
        }
    }

    return { snapshot, loading, refresh };
}
