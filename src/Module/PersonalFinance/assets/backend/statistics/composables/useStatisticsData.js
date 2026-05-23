import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Holds the statistics snapshot + a reactive `months` selector that
 * triggers a refresh whenever the user picks a new period (3 / 6 / 12).
 */
export function useStatisticsData(refreshPath, initial) {
    const snapshot = ref(initial);
    const months = ref(initial.months ?? 6);
    const { loading, request } = useRequest();

    async function refresh() {
        const url = `${refreshPath}?months=${months.value}`;
        const payload = await request(url, null, HttpMethod.Get);
        if (payload && payload.success !== false && payload.snapshot) {
            snapshot.value = payload.snapshot;
        }
    }

    async function setPeriod(value) {
        months.value = value;
        await refresh();
    }

    return { snapshot, months, loading, refresh, setPeriod };
}
