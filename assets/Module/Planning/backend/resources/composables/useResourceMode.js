import { ref, computed, watch } from "vue";
import { useResourceWeek } from "./useResourceWeek.js";

/**
 * Coordinates the "resource grid" view (1 row = 1 user, days as
 * columns) with the rest of the planning state:
 *  - exposes a `viewMode` ref to switch between "calendar" and
 *    "resource"
 *  - drives the data range when in resource mode (via setRange +
 *    loadEvents from the planning context)
 *  - filters the visible user rows based on the people sidebar
 *    selection
 *  - bridges resource-grid events (slot click → create modal,
 *    event click → edit modal) to the existing event form
 */
export function useResourceMode({
    setRange,
    loadEvents,
    peopleSidebar,
    eventForm,
    canManageEvents,
}) {
    const viewMode = ref("calendar");
    const week = useResourceWeek();

    // When in resource mode, the week navigation drives event reloads.
    // The calendar mode handles its own range via FullCalendar.datesSet.
    watch([viewMode, () => week.weekStart.value], () => {
        if (viewMode.value !== "resource") return;
        if (setRange(week.rangeFrom.value, week.rangeTo.value)) {
            loadEvents();
        }
    });

    const visibleUsers = computed(() => {
        if (peopleSidebar.mode.value !== "users") {
            return peopleSidebar.users.value;
        }
        if (peopleSidebar.selectedUserIds.value.size === 0) {
            return peopleSidebar.users.value;
        }
        return peopleSidebar.users.value.filter((user) =>
            peopleSidebar.selectedUserIds.value.has(Number(user.id)),
        );
    });

    function onCreateEvent({ start, user }) {
        if (canManageEvents && !canManageEvents.value) return;
        const startDate = start instanceof Date ? start : new Date(start);
        const endDate = new Date(startDate.getTime() + 60 * 60 * 1000);
        eventForm.openCreate({
            start: startDate,
            startStr: toLocalIso(startDate),
            endStr: toLocalIso(endDate),
            allDay: false,
            attendeeIds: user ? [Number(user.id)] : [],
        });
    }

    function onSelectEvent(event) {
        eventForm.openEdit(event);
    }

    return {
        viewMode,
        week,
        visibleUsers,
        onCreateEvent,
        onSelectEvent,
    };
}

function toLocalIso(date) {
    const pad = (number) => String(number).padStart(2, "0");
    return (
        `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}` +
        `T${pad(date.getHours())}:${pad(date.getMinutes())}`
    );
}
