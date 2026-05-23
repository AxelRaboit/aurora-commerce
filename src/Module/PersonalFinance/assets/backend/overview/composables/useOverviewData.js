import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Holds the Overview snapshot and lets the page refresh it on demand
 * (mirrors useDashboardData). Same idempotent contract — every call
 * rebuilds the full snapshot server-side, no incremental patches.
 */
export function useOverviewData(refreshPath, initial) {
    const snapshot = ref(initial);
    const { loading, request } = useRequest();

    async function refresh() {
        const payload = await request(refreshPath, null, HttpMethod.Get);
        if (payload && payload.success !== false && payload.snapshot) {
            snapshot.value = payload.snapshot;
        }
    }

    return { snapshot, loading, refresh };
}
