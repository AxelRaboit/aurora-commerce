import { ref, computed, watch } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Holds the state shared by every planning-related composable:
 * the list of plannings, the currently-selected planning id, the
 * derived references and the events lazy-loaded for the visible
 * date range.
 */
export function usePlanningContext(initialPlannings, eventsListPath) {
    const { request } = useRequest();

    const plannings = ref([...initialPlannings]);
    const selectedPlanningId = ref(plannings.value[0]?.id ?? null);
    const events = ref([]);
    const currentRange = ref({ from: null, to: null });

    const selectedPlanning = computed(
        () =>
            plannings.value.find(
                (planning) =>
                    Number(planning.id) === Number(selectedPlanningId.value),
            ) ?? null,
    );

    const planningOptions = computed(() =>
        plannings.value.map((planning) => ({
            value: planning.id,
            label: planning.name,
        })),
    );

    async function loadEvents() {
        if (!selectedPlanningId.value || !currentRange.value.from) return;
        const url =
            buildPath(eventsListPath, { id: selectedPlanningId.value }) +
            `?from=${encodeURIComponent(currentRange.value.from)}` +
            `&to=${encodeURIComponent(currentRange.value.to)}`;
        const data = await request(url, null, HttpMethod.Get);
        if (data?.success) events.value = data.items;
    }

    /**
     * Updates the visible range and returns true only when the range
     * actually changed. FullCalendar's `datesSet` callback fires every
     * time options are re-applied (including when we mutate the
     * events list), so we deduplicate to avoid an infinite reload loop.
     */
    function setRange(from, to) {
        if (currentRange.value.from === from && currentRange.value.to === to) {
            return false;
        }
        currentRange.value = { from, to };
        return true;
    }

    watch(selectedPlanningId, () => {
        loadEvents();
    });

    return {
        plannings,
        selectedPlanningId,
        selectedPlanning,
        planningOptions,
        events,
        loadEvents,
        setRange,
    };
}
