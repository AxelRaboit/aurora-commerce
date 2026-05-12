import { ref, computed } from "vue";

/**
 * Holds the visible week (Mon → Sun) for the resource grid view and
 * exposes navigation helpers. Independent from FullCalendar's internal
 * range so the user can switch between views without losing context.
 */
export function useResourceWeek() {
    const today = startOfDay(new Date());
    const weekStart = ref(startOfWeek(today));

    const weekDays = computed(() => {
        const days = [];
        for (let offset = 0; offset < 7; offset += 1) {
            const day = new Date(weekStart.value);
            day.setDate(day.getDate() + offset);
            days.push(day);
        }
        return days;
    });

    const rangeFrom = computed(() => isoStartOfDay(weekStart.value));
    const rangeTo = computed(() => isoEndOfDay(weekDays.value[6]));

    const weekLabel = computed(() => {
        const first = weekDays.value[0];
        const last = weekDays.value[6];
        const sameMonth = first.getMonth() === last.getMonth();
        const fmtDay = (d, withMonth) =>
            new Intl.DateTimeFormat(document.documentElement.lang || "fr", {
                day: "numeric",
                month: withMonth ? "short" : undefined,
                year:
                    withMonth && first.getFullYear() !== last.getFullYear()
                        ? "numeric"
                        : undefined,
            }).format(d);
        return `${fmtDay(first, !sameMonth)} – ${fmtDay(last, true)} ${last.getFullYear()}`;
    });

    function previousWeek() {
        const next = new Date(weekStart.value);
        next.setDate(next.getDate() - 7);
        weekStart.value = next;
    }

    function nextWeek() {
        const next = new Date(weekStart.value);
        next.setDate(next.getDate() + 7);
        weekStart.value = next;
    }

    function goToToday() {
        weekStart.value = startOfWeek(startOfDay(new Date()));
    }

    return {
        weekStart,
        weekDays,
        weekLabel,
        rangeFrom,
        rangeTo,
        previousWeek,
        nextWeek,
        goToToday,
    };
}

function startOfDay(date) {
    const day = new Date(date);
    day.setHours(0, 0, 0, 0);
    return day;
}

function startOfWeek(date) {
    const day = startOfDay(date);
    const dayOfWeek = day.getDay(); // 0 = Sunday … 6 = Saturday
    const diffToMonday = (dayOfWeek + 6) % 7; // 0 if Mon, 6 if Sun
    day.setDate(day.getDate() - diffToMonday);
    return day;
}

function isoStartOfDay(date) {
    const day = startOfDay(date);
    return day.toISOString();
}

function isoEndOfDay(date) {
    const day = new Date(date);
    day.setHours(23, 59, 59, 999);
    return day.toISOString();
}
