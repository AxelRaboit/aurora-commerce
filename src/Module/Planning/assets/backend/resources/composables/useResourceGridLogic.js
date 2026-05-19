import { computed } from "vue";
import { STATUS_TONES } from "../../events/composables/useEventFilters.js";

export function useResourceGridLogic(props, emit) {
    function isSameDay(isoDate, day) {
        if (!isoDate) return false;
        const date = new Date(isoDate);
        return (
            date.getFullYear() === day.getFullYear() &&
            date.getMonth() === day.getMonth() &&
            date.getDate() === day.getDate()
        );
    }

    function isToday(day) {
        const now = new Date();
        return (
            day.getFullYear() === now.getFullYear() &&
            day.getMonth() === now.getMonth() &&
            day.getDate() === now.getDate()
        );
    }

    function formatDayHeader(day) {
        return new Intl.DateTimeFormat(document.documentElement.lang || "fr", {
            weekday: "short",
            day: "2-digit",
        }).format(day);
    }

    function eventsFor(user, day) {
        return props.events.filter(
            (event) =>
                event.attendees?.some(
                    (attendee) => Number(attendee.id) === Number(user.id),
                ) && isSameDay(event.startAt, day),
        );
    }

    function eventsWithoutAttendees(day) {
        return props.events.filter(
            (event) =>
                !event.attendees?.length && isSameDay(event.startAt, day),
        );
    }

    const hasUnassignedRow = computed(() =>
        props.events.some(
            (event) =>
                !event.attendees?.length &&
                props.weekDays.some((day) => isSameDay(event.startAt, day)),
        ),
    );

    function eventStyle(event) {
        const tone = STATUS_TONES[event.status] ?? STATUS_TONES.confirmed;
        const color = tone.bg ?? props.baseColor;
        return {
            backgroundColor: color,
            borderColor: color,
            opacity: tone.opacity,
        };
    }

    function onCellClick(user, day) {
        const start = new Date(day);
        start.setHours(9, 0, 0, 0);
        const end = new Date(start);
        end.setHours(10, 0, 0, 0);
        emit("create-event", { day, user, start, end });
    }

    return {
        isSameDay,
        isToday,
        formatDayHeader,
        eventsFor,
        eventsWithoutAttendees,
        hasUnassignedRow,
        eventStyle,
        onCellClick,
    };
}
