<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import EventChip from "./EventChip.vue";

const props = defineProps({
    weekDays: { type: Array, required: true },
    weekLabel: { type: String, required: true },
    users: { type: Array, required: true },
    events: { type: Array, required: true },
    baseColor: { type: String, default: "#3b82f6" },
});

const emit = defineEmits([
    "previous-week",
    "next-week",
    "today",
    "create-event",
    "select-event",
]);

const { t } = useI18n();

const STATUS_TONES = {
    confirmed: { bg: null, opacity: 1, classes: "" },
    tentative: { bg: null, opacity: 0.65, classes: "fc-event-tentative" },
    cancelled: { bg: "#9ca3af", opacity: 0.55, classes: "line-through" },
};

function isSameDay(isoDate, day) {
    if (!isoDate) return false;
    const date = new Date(isoDate);
    return (
        date.getFullYear() === day.getFullYear()
        && date.getMonth() === day.getMonth()
        && date.getDate() === day.getDate()
    );
}

function isToday(day) {
    const now = new Date();
    return (
        day.getFullYear() === now.getFullYear()
        && day.getMonth() === now.getMonth()
        && day.getDate() === now.getDate()
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
            event.attendees?.some((attendee) => Number(attendee.id) === Number(user.id))
            && isSameDay(event.startAt, day),
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
            !event.attendees?.length
            && props.weekDays.some((day) => isSameDay(event.startAt, day)),
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
</script>

<template>
    <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
        <!-- Toolbar -->
        <div class="flex items-center justify-between gap-3 px-3 py-2 border-b border-line/40">
            <div class="flex items-center gap-1">
                <AppIconButton :title="t('backend.plannings.resourceView.previous')" v-on:click="emit('previous-week')">
                    <ChevronLeft class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton :title="t('backend.plannings.resourceView.next')" v-on:click="emit('next-week')">
                    <ChevronRight class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppButton variant="ghost" size="sm" v-on:click="emit('today')">
                    {{ t("backend.plannings.resourceView.today") }}
                </AppButton>
            </div>
            <span class="text-sm font-medium text-primary">{{ weekLabel }}</span>
            <span class="text-xs text-secondary">
                {{ t("backend.plannings.resourceView.userCount", { n: users.length }) }}
            </span>
        </div>

        <!-- Empty state -->
        <div
            v-if="!users.length"
            class="px-6 py-12 text-center text-sm text-muted"
        >
            {{ t("backend.plannings.resourceView.noUsers") }}
        </div>

        <!-- Grid -->
        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-surface-2/50">
                        <th class="sticky left-0 z-10 bg-surface-2/95 backdrop-blur min-w-45 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-muted border-b border-line/40">
                            {{ t("backend.plannings.resourceView.user") }}
                        </th>
                        <th
                            v-for="day in weekDays"
                            :key="day.toISOString()"
                            class="px-2 py-2 text-center text-xs font-medium border-b border-line/40 min-w-30"
                            :class="isToday(day) ? 'bg-accent-500/10 text-accent-400' : 'text-muted'"
                        >
                            {{ formatDayHeader(day) }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/30">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-surface-2/20">
                        <td class="sticky left-0 z-10 bg-surface backdrop-blur px-3 py-2 font-medium text-primary border-r border-line/40 align-top">
                            <span class="truncate block">{{ user.name }}</span>
                        </td>
                        <td
                            v-for="day in weekDays"
                            :key="day.toISOString()"
                            class="px-1 py-1 align-top border-r border-line/20 cursor-pointer hover:bg-surface-2/40 transition-colors"
                            :class="isToday(day) ? 'bg-accent-500/5' : ''"
                            v-on:click="onCellClick(user, day)"
                        >
                            <div class="flex flex-col gap-1 min-h-15">
                                <EventChip
                                    v-for="event in eventsFor(user, day)"
                                    :key="event.id"
                                    :event="event"
                                    :background-color="eventStyle(event).backgroundColor"
                                    :extra-class="STATUS_TONES[event.status]?.classes"
                                    v-on:select="emit('select-event', $event)"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="hasUnassignedRow" class="bg-surface-2/20">
                        <td class="sticky left-0 z-10 bg-surface-2/95 backdrop-blur px-3 py-2 italic text-secondary border-r border-line/40 align-top">
                            {{ t("backend.plannings.resourceView.unassigned") }}
                        </td>
                        <td
                            v-for="day in weekDays"
                            :key="day.toISOString()"
                            class="px-1 py-1 align-top border-r border-line/20"
                        >
                            <div class="flex flex-col gap-1 min-h-10">
                                <EventChip
                                    v-for="event in eventsWithoutAttendees(day)"
                                    :key="event.id"
                                    :event="event"
                                    :background-color="eventStyle(event).backgroundColor"
                                    :extra-class="STATUS_TONES[event.status]?.classes"
                                    v-on:select="emit('select-event', $event)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
