<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { usePlanningForm } from "./composables/usePlanningForm.js";
import { useEventForm } from "./composables/useEventForm.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import { Plus, Pencil, Trash2 } from "lucide-vue-next";

const props = defineProps({
    plannings: { type: Array, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    eventsListPath: { type: String, required: true },
    eventCreatePath: { type: String, required: true },
    eventUpdatePath: { type: String, required: true },
    eventDeletePath: { type: String, required: true },
});

const { t } = useI18n();
const { request } = useApiRequest();

const plannings = ref([...props.plannings]);
const selectedPlanningId = ref(plannings.value[0]?.id ?? null);
const events = ref([]);

const selectedPlanning = computed(
    () => plannings.value.find((planning) => planning.id === selectedPlanningId.value) ?? null,
);

const planningForm = usePlanningForm(plannings, props.createPath, props.updatePath);
const eventForm = useEventForm(
    events,
    props.eventCreatePath,
    props.eventUpdatePath,
    props.eventDeletePath,
);

const calendarEvents = computed(() =>
    events.value.map((event) => ({
        id: String(event.id),
        title: event.title,
        start: event.startAt,
        end: event.endAt,
        allDay: event.allDay,
        backgroundColor: selectedPlanning.value?.color ?? "#3b82f6",
        borderColor: selectedPlanning.value?.color ?? "#3b82f6",
        editable: event.editable,
        extendedProps: { raw: event },
    })),
);

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,timeGridDay",
    },
    locale: document.documentElement.lang || "fr",
    height: "auto",
    selectable: true,
    editable: true,
    events: calendarEvents.value,
    select: onSlotSelect,
    eventClick: onEventClick,
    eventDrop: onEventDrop,
    eventResize: onEventDrop,
    datesSet: onDatesChange,
}));

let currentRange = { from: null, to: null };

async function loadEvents() {
    if (!selectedPlanningId.value || !currentRange.from) return;
    const url =
        buildPath(props.eventsListPath, { id: selectedPlanningId.value }) +
        `?from=${encodeURIComponent(currentRange.from)}&to=${encodeURIComponent(currentRange.to)}`;
    const data = await request(url, null, HttpMethod.Get);
    if (data?.success) events.value = data.items;
}

function onDatesChange(info) {
    currentRange = {
        from: info.startStr,
        to: info.endStr,
    };
    loadEvents();
}

function onSlotSelect(slot) {
    if (!selectedPlanningId.value) return;
    eventForm.openCreate(slot);
}

function onEventClick(info) {
    const raw = info.event.extendedProps.raw;
    if (raw) eventForm.openEdit(raw);
}

async function onEventDrop(info) {
    const raw = info.event.extendedProps.raw;
    if (!raw || !raw.editable) {
        info.revert();
        return;
    }
    const url = buildPath(props.eventUpdatePath, { eventId: raw.id });
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

async function deletePlanning(planning) {
    if (!confirm(t("backend.plannings.delete_confirm"))) return;
    const url = buildPath(props.deletePath, { id: planning.id });
    const data = await request(url, {});
    if (!data?.success) {
        toast.error(t("shared.common.error"));
        return;
    }
    plannings.value = plannings.value.filter((entry) => entry.id !== planning.id);
    if (selectedPlanningId.value === planning.id) {
        selectedPlanningId.value = plannings.value[0]?.id ?? null;
    }
    toast.success(t("shared.common.deleted"));
}

watch(selectedPlanningId, () => {
    loadEvents();
});

onMounted(() => {
    if (selectedPlanningId.value) loadEvents();
});

const visibilityOptions = [
    { value: "private", label: t("backend.plannings.visibility.private") },
    { value: "agency", label: t("backend.plannings.visibility.agency") },
    { value: "public", label: t("backend.plannings.visibility.public") },
];

const statusOptions = [
    { value: "tentative", label: t("backend.planning_events.status.tentative") },
    { value: "confirmed", label: t("backend.planning_events.status.confirmed") },
    { value: "cancelled", label: t("backend.planning_events.status.cancelled") },
];
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-sm">
                <span class="text-zinc-600 dark:text-zinc-400">{{ t("backend.plannings.title") }} :</span>
                <select
                    v-model="selectedPlanningId"
                    class="rounded border border-zinc-300 bg-white px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                >
                    <option v-for="planning in plannings" :key="planning.id" :value="planning.id">
                        {{ planning.name }}
                    </option>
                </select>
            </label>
            <span
                v-if="selectedPlanning"
                class="inline-block h-3 w-3 rounded-full"
                :style="{ backgroundColor: selectedPlanning.color }"
            />
            <AppIconButton
                v-if="selectedPlanning"
                :title="t('backend.plannings.edit')"
                v-on:click="planningForm.openEdit(selectedPlanning)"
            >
                <Pencil class="h-4 w-4" />
            </AppIconButton>
            <AppIconButton
                v-if="selectedPlanning"
                :title="t('backend.plannings.delete')"
                v-on:click="deletePlanning(selectedPlanning)"
            >
                <Trash2 class="h-4 w-4" />
            </AppIconButton>
            <div class="ml-auto">
                <AppButton v-on:click="planningForm.openCreate()">
                    <Plus class="h-4 w-4" />
                    {{ t("backend.plannings.new") }}
                </AppButton>
            </div>
        </div>

        <div v-if="!selectedPlanning" class="rounded border border-dashed border-zinc-300 p-8 text-center text-zinc-500 dark:border-zinc-700">
            <p>{{ t("backend.plannings.title") }}</p>
            <AppButton class="mt-3" v-on:click="planningForm.openCreate()">
                <Plus class="h-4 w-4" />
                {{ t("backend.plannings.new") }}
            </AppButton>
        </div>

        <div v-else class="rounded border border-zinc-200 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
            <FullCalendar :options="calendarOptions" />
        </div>

        <AppModal v-model:open="planningForm.editModal.open" :title="planningForm.editModal.planning ? t('backend.plannings.edit') : t('backend.plannings.new')">
            <form class="flex flex-col gap-3" v-on:submit.prevent="planningForm.submit()">
                <AppInput
                    v-model="planningForm.editForm.name"
                    :label="t('backend.plannings.fields.name')"
                    :error="planningForm.editModal.errors.name"
                    required
                />
                <AppTextarea
                    v-model="planningForm.editForm.description"
                    :label="t('backend.plannings.fields.description')"
                />
                <div class="grid grid-cols-2 gap-3">
                    <AppInput
                        v-model="planningForm.editForm.color"
                        :label="t('backend.plannings.fields.color')"
                        type="color"
                        :error="planningForm.editModal.errors.color"
                    />
                    <AppInput
                        v-model="planningForm.editForm.timezone"
                        :label="t('backend.plannings.fields.timezone')"
                        :error="planningForm.editModal.errors.timezone"
                    />
                </div>
                <AppSelect
                    v-model="planningForm.editForm.visibility"
                    :label="t('backend.plannings.fields.visibility')"
                    :options="visibilityOptions"
                    :error="planningForm.editModal.errors.visibility"
                />
                <slot name="extra-form-fields" :form="planningForm.editForm" :errors="planningForm.editModal.errors" />
                <div class="flex justify-end gap-2 pt-2">
                    <AppButton type="button" variant="ghost" v-on:click="planningForm.editModal.open = false">
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton type="submit" :loading="planningForm.editModal.saving">
                        {{ t("shared.common.save") }}
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal v-model:open="eventForm.editModal.open" :title="eventForm.editModal.event ? t('backend.planning_events.edit') : t('backend.planning_events.new')">
            <form class="flex flex-col gap-3" v-on:submit.prevent="eventForm.submit(selectedPlanningId)">
                <p
                    v-if="eventForm.editModal.readOnly"
                    class="rounded bg-amber-50 p-2 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200"
                >
                    {{ t("backend.planning_events.errors.source_locked") }}
                </p>
                <AppInput
                    v-model="eventForm.editForm.title"
                    :label="t('backend.planning_events.fields.title')"
                    :error="eventForm.editModal.errors.title"
                    :disabled="eventForm.editModal.readOnly"
                    required
                />
                <AppTextarea
                    v-model="eventForm.editForm.description"
                    :label="t('backend.planning_events.fields.description')"
                    :disabled="eventForm.editModal.readOnly"
                />
                <AppInput
                    v-model="eventForm.editForm.location"
                    :label="t('backend.planning_events.fields.location')"
                    :disabled="eventForm.editModal.readOnly"
                />
                <div class="grid grid-cols-2 gap-3">
                    <AppInput
                        v-model="eventForm.editForm.startAt"
                        :label="t('backend.planning_events.fields.startAt')"
                        type="datetime-local"
                        :error="eventForm.editModal.errors.startAt"
                        :disabled="eventForm.editModal.readOnly"
                    />
                    <AppInput
                        v-model="eventForm.editForm.endAt"
                        :label="t('backend.planning_events.fields.endAt')"
                        type="datetime-local"
                        :error="eventForm.editModal.errors.endAt"
                        :disabled="eventForm.editModal.readOnly"
                    />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="eventForm.editForm.allDay"
                        type="checkbox"
                        :disabled="eventForm.editModal.readOnly"
                    >
                    {{ t("backend.planning_events.fields.allDay") }}
                </label>
                <AppSelect
                    v-model="eventForm.editForm.status"
                    :label="t('backend.planning_events.fields.status')"
                    :options="statusOptions"
                    :disabled="eventForm.editModal.readOnly"
                />
                <div class="flex items-center justify-between gap-2 pt-2">
                    <AppButton
                        v-if="eventForm.editModal.event && !eventForm.editModal.readOnly"
                        type="button"
                        variant="danger"
                        v-on:click="eventForm.remove()"
                    >
                        <Trash2 class="h-4 w-4" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                    <div class="ml-auto flex gap-2">
                        <AppButton type="button" variant="ghost" v-on:click="eventForm.editModal.open = false">
                            {{ t("shared.common.cancel") }}
                        </AppButton>
                        <AppButton
                            v-if="!eventForm.editModal.readOnly"
                            type="submit"
                            :loading="eventForm.editModal.saving"
                        >
                            {{ t("shared.common.save") }}
                        </AppButton>
                    </div>
                </div>
            </form>
        </AppModal>
    </div>
</template>
