import { computed } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import listPlugin from "@fullcalendar/list";
import interactionPlugin from "@fullcalendar/interaction";
import frLocale from "@fullcalendar/core/locales/fr";
import enLocale from "@fullcalendar/core/locales/en-gb";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";

/**
 * Wires FullCalendar config, locales and event handlers to the planning
 * domain composables. The host component just spreads `options.value`
 * into <FullCalendar :options="..." />.
 */
export function useCalendar({
    selectedPlanningId,
    events,
    calendarEvents,
    eventForm,
    eventUpdatePath,
    canManageEvents,
    onSlotEmpty,
    onRangeChange,
}) {
    const { t } = useI18n();
    const { request } = useRequest();
    const currentLocale = document.documentElement.lang || "fr";

    function onEventMount(info) {
        const raw = info.event.extendedProps.raw;
        if (!raw) return;
        const tooltipParts = [raw.title];
        if (raw.location) tooltipParts.push(`📍 ${raw.location}`);
        if (raw.attendees?.length) {
            tooltipParts.push(
                `👥 ${raw.attendees.map((attendee) => attendee.name).join(", ")}`,
            );
        }
        if (raw.sourceLabel) tooltipParts.push(`🔗 ${raw.sourceLabel}`);
        info.el.setAttribute("title", tooltipParts.join("\n"));
    }

    function isManageAllowed() {
        return canManageEvents ? canManageEvents.value : true;
    }

    function onSlotSelect(slot) {
        if (!isManageAllowed()) return;
        if (!selectedPlanningId.value) {
            onSlotEmpty?.();
            return;
        }
        eventForm.openCreate(slot);
    }

    function onEventClick(info) {
        const raw = info.event.extendedProps.raw;
        if (raw) eventForm.openEdit(raw);
    }

    async function onEventDrop(info) {
        const raw = info.event.extendedProps.raw;
        if (!raw || !raw.editable || !isManageAllowed()) {
            info.revert();
            return;
        }
        const url = buildPath(eventUpdatePath, { eventId: raw.id });
        const data = await request(url, {
            title: raw.title,
            description: raw.description,
            location: raw.location,
            startAt: info.event.startStr,
            endAt: info.event.endStr || info.event.startStr,
            allDay: info.event.allDay,
            status: raw.status,
            attendeeIds: raw.attendees.map((attendee) => attendee.id),
        });
        if (!data?.success) {
            info.revert();
            toast.error(t("shared.common.error"));
            return;
        }
        const index = events.value.findIndex((event) => event.id === raw.id);
        if (index !== -1) events.value[index] = data.event;
    }

    function onDatesChange(info) {
        onRangeChange?.(info.startStr, info.endStr);
    }

    const options = computed(() => ({
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        initialView: "dayGridMonth",
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek",
        },
        locales: [frLocale, enLocale],
        locale: currentLocale,
        firstDay: 1,
        weekNumbers: true,
        weekNumberFormat: { week: "narrow" },
        nowIndicator: true,
        navLinks: true,
        height: "auto",
        selectable: isManageAllowed(),
        selectMirror: true,
        editable: isManageAllowed(),
        dayMaxEvents: 3,
        slotMinTime: "06:00:00",
        slotMaxTime: "22:00:00",
        slotDuration: "00:30:00",
        scrollTime: "08:00:00",
        eventTimeFormat: { hour: "2-digit", minute: "2-digit", hour12: false },
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5],
            startTime: "09:00",
            endTime: "18:00",
        },
        events: calendarEvents.value,
        eventDidMount: onEventMount,
        select: onSlotSelect,
        eventClick: onEventClick,
        eventDrop: onEventDrop,
        eventResize: onEventDrop,
        datesSet: onDatesChange,
    }));

    return { options };
}
