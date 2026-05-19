import { ref, computed } from "vue";

/**
 * Status-based event filtering + visual tone resolution.
 * STATUS_TONES is the single source of truth for how each status
 * renders both in the legend chips and on the calendar surface.
 */
export const STATUS_TONES = {
    confirmed: { bg: null, opacity: 1, classes: "" },
    tentative: { bg: null, opacity: 0.6, classes: "fc-event-tentative" },
    cancelled: { bg: "#9ca3af", opacity: 0.5, classes: "fc-event-cancelled" },
};

export function useEventFilters(events, baseColorRef, options = {}) {
    const visibleStatuses = ref(
        new Set(["confirmed", "tentative", "cancelled"]),
    );

    const extraMatcherRef = options.extraMatcher ?? null;

    const filteredEvents = computed(() =>
        events.value.filter((event) => {
            if (!visibleStatuses.value.has(event.status)) return false;
            const extra = extraMatcherRef?.value;
            if (extra && !extra(event)) return false;
            return true;
        }),
    );

    const calendarEvents = computed(() =>
        filteredEvents.value.map((event) => {
            const baseColor = baseColorRef.value ?? "#3b82f6";
            const tone = STATUS_TONES[event.status] ?? STATUS_TONES.confirmed;
            return {
                id: String(event.id),
                title: event.title,
                start: event.startAt,
                end: event.endAt,
                allDay: event.allDay,
                backgroundColor: tone.bg ?? baseColor,
                borderColor: tone.bg ?? baseColor,
                editable: event.editable,
                classNames: tone.classes,
                extendedProps: { raw: event },
            };
        }),
    );

    function toggleStatus(status) {
        const next = new Set(visibleStatuses.value);
        next.has(status) ? next.delete(status) : next.add(status);
        visibleStatuses.value = next;
    }

    return {
        STATUS_TONES,
        visibleStatuses,
        filteredEvents,
        calendarEvents,
        toggleStatus,
    };
}
