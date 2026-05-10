<script setup>
import { onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { usePlanningPrivileges } from "./composables/usePlanningPrivileges.js";
import FullCalendar from "@fullcalendar/vue3";
import { Plus, Pencil, Trash2, Save, X, CalendarDays, Users, Building2, LayoutGrid, Rows3, CheckSquare, Square } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppCheckbox from "@/shared/components/form/AppCheckbox.vue";
import AppFieldLabel from "@/shared/components/form/AppFieldLabel.vue";
import AppDatePicker from "@/shared/components/form/AppDatePicker.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { usePlanningContext } from "./composables/usePlanningContext.js";
import { usePlanningForm } from "./composables/usePlanningForm.js";
import { usePlanningDelete } from "./composables/usePlanningDelete.js";
import { useEventForm } from "./composables/useEventForm.js";
import { useEventDelete } from "./composables/useEventDelete.js";
import { useEventFilters } from "./composables/useEventFilters.js";
import { useCalendar } from "./composables/useCalendar.js";
import { usePlanningFormOptions } from "./composables/usePlanningFormOptions.js";
import { usePeopleSidebar } from "./composables/usePeopleSidebar.js";
import { useResourceMode } from "./composables/useResourceMode.js";
import ResourceGridView from "./ResourceGridView.vue";

const props = defineProps({
    plannings: { type: Array, default: () => [] },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    eventsListPath: { type: String, required: true },
    eventCreatePath: { type: String, required: true },
    eventUpdatePath: { type: String, required: true },
    eventDeletePath: { type: String, required: true },
    usersSelectablePath: { type: String, required: true },
    agenciesSelectablePath: { type: String, required: true },
});

const { t } = useI18n();
const { can } = usePrivileges();
const {
    canCreatePlannings, canEditPlannings, canDeletePlannings, canManagePlannings,
    canCreateEvents, canEditEvents, canDeleteEvents, canManageEvents,
} = usePlanningPrivileges(can);

// --- Domain state -----------------------------------------------------
const {
    plannings,
    selectedPlanningId,
    selectedPlanning,
    planningOptions,
    events,
    loadEvents,
    setRange,
} = usePlanningContext(props.plannings, props.eventsListPath);


// --- CRUD composables -------------------------------------------------
const planningForm = usePlanningForm(plannings, props.createPath, props.updatePath);
const { deletingPlanning, confirmDelete: confirmDeletePlanning } = usePlanningDelete(
    plannings,
    selectedPlanningId,
    props.deletePath,
);

const eventForm = useEventForm(events, props.eventCreatePath, props.eventUpdatePath);
const { deletingEvent, confirmDelete: confirmDeleteEvent } = useEventDelete(
    events,
    props.eventDeletePath,
    {
        onDeleted: () => {
            eventForm.editModal.open = false;
        },
    },
);

// --- People filter (sidebar) ------------------------------------------
const peopleSidebar = usePeopleSidebar(
    props.usersSelectablePath,
    props.agenciesSelectablePath,
    plannings,
);
const eventMatcher = computed(() => peopleSidebar.buildEventMatcher(plannings));

// --- Display + calendar -----------------------------------------------
const baseColor = computed(() => selectedPlanning.value?.color ?? null);
const { STATUS_TONES, visibleStatuses, filteredEvents, calendarEvents, toggleStatus } =
    useEventFilters(events, baseColor, { extraMatcher: eventMatcher });

const { options: calendarOptions } = useCalendar({
    selectedPlanningId,
    events,
    calendarEvents,
    eventForm,
    eventUpdatePath: props.eventUpdatePath,
    canManageEvents,
    onRangeChange: (from, to) => {
        if (setRange(from, to)) loadEvents();
    },
});

const { visibilityOptions, statusOptions, timezoneOptions } = usePlanningFormOptions();

const resourceMode = useResourceMode({
    setRange,
    loadEvents,
    peopleSidebar,
    eventForm,
    canManageEvents,
});

onMounted(() => {
    if (selectedPlanningId.value) loadEvents();
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-primary">{{ t("backend.plannings.title") }}</h1>
            <AppButton v-if="canManagePlannings" variant="primary" size="md" v-on:click="planningForm.openCreate()">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t("backend.plannings.new") }}
            </AppButton>
        </div>

        <div
            v-if="!plannings.length"
            class="bg-surface border border-line/60 rounded-xl p-12 text-center"
        >
            <CalendarDays class="mx-auto mb-3 h-10 w-10 text-muted opacity-40" :stroke-width="1.5" />
            <p class="mb-4 text-secondary text-sm">{{ t("backend.plannings.empty") }}</p>
            <AppButton v-if="canManagePlannings" variant="primary" size="md" v-on:click="planningForm.openCreate()">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t("backend.plannings.new") }}
            </AppButton>
        </div>

        <template v-else>
            <div class="grid gap-4 lg:grid-cols-[240px_1fr]">
                <aside class="bg-surface border border-line/60 rounded-xl p-3 space-y-3 self-start lg:sticky lg:top-4">
                    <div class="grid grid-cols-2 gap-1 rounded-lg bg-surface-2/60 p-1">
                        <AppTab
                            size="xs"
                            shape-class="rounded-md"
                            align="center"
                            :active="peopleSidebar.mode.value === 'users'"
                            v-on:click="peopleSidebar.mode.value = 'users'"
                        >
                            <Users class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("backend.plannings.sidebar.users") }}
                        </AppTab>
                        <AppTab
                            size="xs"
                            shape-class="rounded-md"
                            align="center"
                            :active="peopleSidebar.mode.value === 'agencies'"
                            v-on:click="peopleSidebar.mode.value = 'agencies'"
                        >
                            <Building2 class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("backend.plannings.sidebar.agencies") }}
                        </AppTab>
                    </div>

                    <AppSearchInput
                        v-model="peopleSidebar.searchQuery.value"
                        :placeholder="peopleSidebar.mode.value === 'users'
                            ? t('backend.plannings.sidebar.searchUsersPlaceholder')
                            : t('backend.plannings.sidebar.searchAgenciesPlaceholder')"
                    />

                    <div class="flex flex-col gap-1.5">
                        <AppButton
                            variant="ghost"
                            size="sm"
                            class="w-full justify-center"
                            v-on:click="peopleSidebar.allVisibleSelected.value
                                ? peopleSidebar.clearSelection()
                                : peopleSidebar.selectAllVisible()"
                        >
                            <CheckSquare
                                v-if="!peopleSidebar.allVisibleSelected.value"
                                class="w-3.5 h-3.5"
                                :stroke-width="2"
                            />
                            <Square
                                v-else
                                class="w-3.5 h-3.5"
                                :stroke-width="2"
                            />
                            {{ peopleSidebar.allVisibleSelected.value
                                ? t("backend.plannings.sidebar.deselectAll")
                                : t("backend.plannings.sidebar.selectAll") }}
                        </AppButton>
                        <AppButton
                            v-if="peopleSidebar.hasFilter.value"
                            variant="ghost"
                            size="sm"
                            class="w-full justify-center"
                            v-on:click="peopleSidebar.clearSelection()"
                        >
                            <X class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("backend.plannings.sidebar.clear") }}
                        </AppButton>
                    </div>

                    <div v-if="peopleSidebar.mode.value === 'users'" class="space-y-1 max-h-[60vh] overflow-y-auto">
                        <p
                            v-if="!peopleSidebar.filteredUsers.value.length"
                            class="text-xs text-muted py-3 text-center"
                        >
                            {{ peopleSidebar.searchQuery.value
                                ? t("backend.plannings.sidebar.noResults")
                                : t("backend.plannings.sidebar.noUsers") }}
                        </p>
                        <label
                            v-for="user in peopleSidebar.filteredUsers.value"
                            :key="user.id"
                            class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-surface-2/60 cursor-pointer text-sm"
                        >
                            <AppCheckbox
                                :model-value="peopleSidebar.selectedUserIds.value.has(Number(user.id))"
                                v-on:update:model-value="peopleSidebar.toggleUser(Number(user.id))"
                            />
                            <span class="truncate text-primary">{{ user.name }}</span>
                        </label>
                    </div>

                    <div v-else class="space-y-1 max-h-[60vh] overflow-y-auto">
                        <p
                            v-if="!peopleSidebar.filteredAgencies.value.length"
                            class="text-xs text-muted py-3 text-center"
                        >
                            {{ peopleSidebar.searchQuery.value
                                ? t("backend.plannings.sidebar.noResults")
                                : t("backend.plannings.sidebar.noAgencies") }}
                        </p>
                        <label
                            v-for="agency in peopleSidebar.filteredAgencies.value"
                            :key="agency.value"
                            class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-surface-2/60 cursor-pointer text-sm"
                        >
                            <AppCheckbox
                                :model-value="peopleSidebar.selectedAgencyIds.value.has(Number(agency.value))"
                                v-on:update:model-value="peopleSidebar.toggleAgency(Number(agency.value))"
                            />
                            <span class="truncate text-primary">{{ agency.label }}</span>
                        </label>
                    </div>
                </aside>

                <div class="space-y-4 min-w-0">
                    <div class="bg-surface border border-line/60 rounded-xl p-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="min-w-60 flex-1 max-w-sm">
                                <AppSelect v-model="selectedPlanningId" :options="planningOptions" />
                            </div>
                            <span
                                v-if="selectedPlanning"
                                class="inline-block h-3 w-3 rounded-full ring-2 ring-line/40"
                                :style="{ backgroundColor: selectedPlanning.color }"
                            />
                            <div v-if="selectedPlanning && canManagePlannings" class="flex items-center gap-1">
                                <AppIconButton
                                    color="accent"
                                    :title="t('backend.plannings.edit')"
                                    v-on:click="planningForm.openEdit(selectedPlanning)"
                                >
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton
                                    color="rose"
                                    :title="t('backend.plannings.delete')"
                                    v-on:click="deletingPlanning = selectedPlanning"
                                >
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                            <div class="ml-auto flex items-center gap-2 flex-wrap">
                                <div class="flex items-center gap-1 rounded-lg bg-surface-2/60 p-1">
                                    <AppTab
                                        size="xs"
                                        shape-class="rounded-md"
                                        :active="resourceMode.viewMode.value === 'calendar'"
                                        v-on:click="resourceMode.viewMode.value = 'calendar'"
                                    >
                                        <LayoutGrid class="w-3.5 h-3.5" :stroke-width="2" />
                                        {{ t("backend.plannings.viewMode.calendar") }}
                                    </AppTab>
                                    <AppTab
                                        size="xs"
                                        shape-class="rounded-md"
                                        :active="resourceMode.viewMode.value === 'resource'"
                                        v-on:click="resourceMode.viewMode.value = 'resource'"
                                    >
                                        <Rows3 class="w-3.5 h-3.5" :stroke-width="2" />
                                        {{ t("backend.plannings.viewMode.resource") }}
                                    </AppTab>
                                </div>
                                <span class="text-xs text-secondary hidden sm:inline">
                                    {{ t("backend.planning_events.legend") }} :
                                </span>
                                <AppTab
                                    v-for="(tone, status) in STATUS_TONES"
                                    :key="status"
                                    size="xs"
                                    shape-class="rounded-full"
                                    :active="visibleStatuses.has(status)"
                                    v-on:click="toggleStatus(status)"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full"
                                        :style="{ backgroundColor: tone.bg ?? selectedPlanning.color, opacity: tone.opacity }"
                                    />
                                    {{ t(`backend.planning_events.status.${status}`) }}
                                </AppTab>
                            </div>
                        </div>
                    </div>

                    <div v-if="resourceMode.viewMode.value === 'calendar'" class="bg-surface border border-line/60 rounded-xl p-3">
                        <FullCalendar :options="calendarOptions" />
                    </div>

                    <ResourceGridView
                        v-else
                        :week-days="resourceMode.week.weekDays.value"
                        :week-label="resourceMode.week.weekLabel.value"
                        :users="resourceMode.visibleUsers.value"
                        :events="filteredEvents"
                        :base-color="selectedPlanning?.color ?? '#3b82f6'"
                        v-on:previous-week="resourceMode.week.previousWeek()"
                        v-on:next-week="resourceMode.week.nextWeek()"
                        v-on:today="resourceMode.week.goToToday()"
                        v-on:create-event="resourceMode.onCreateEvent"
                        v-on:select-event="resourceMode.onSelectEvent"
                    />

                    <p class="text-xs text-muted text-right">
                        {{ t("backend.planning_events.count", { n: filteredEvents.length }) }}
                    </p>
                </div>
            </div>
        </template>

        <!-- Edit / Create planning -->
        <AppModal
            :show="planningForm.editModal.open"
            max-width="md"
            :title="planningForm.editModal.planning ? t('backend.plannings.edit') : t('backend.plannings.new')"
            v-on:close="planningForm.editModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="planningForm.submit()">
                <AppInput
                    v-model="planningForm.editForm.name"
                    :label="t('backend.plannings.fields.name')"
                    :placeholder="t('backend.plannings.fields.namePlaceholder')"
                    :error="planningForm.editModal.errors.name ?? ''"
                    :required="true"
                />
                <AppTextarea
                    v-model="planningForm.editForm.description"
                    :label="t('backend.plannings.fields.description')"
                    :placeholder="t('backend.plannings.fields.descriptionPlaceholder')"
                />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppInput
                        v-model="planningForm.editForm.color"
                        :label="t('backend.plannings.fields.color')"
                        type="color"
                        :error="planningForm.editModal.errors.color ?? ''"
                    />
                    <AppSelect
                        v-model="planningForm.editForm.timezone"
                        :label="t('backend.plannings.fields.timezone')"
                        :options="timezoneOptions"
                        :error="planningForm.editModal.errors.timezone ?? ''"
                    />
                </div>
                <AppSelect
                    v-model="planningForm.editForm.visibility"
                    :label="t('backend.plannings.fields.visibility')"
                    :options="visibilityOptions"
                    :error="planningForm.editModal.errors.visibility ?? ''"
                />
                <slot name="extra-form-fields" :edit-form="planningForm.editForm" :errors="planningForm.editModal.errors" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="planningForm.editModal.open = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="planningForm.editModal.saving">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete planning confirm -->
        <AppModal :show="!!deletingPlanning" max-width="sm" v-on:close="deletingPlanning = null">
            <p class="text-sm text-primary">
                {{ t("backend.plannings.delete_confirm", { name: deletingPlanning?.name ?? "" }) }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingPlanning = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeletePlanning">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Edit / Create event -->
        <AppModal
            :show="eventForm.editModal.open"
            max-width="md"
            :scrollable="false"
            :title="eventForm.editModal.event ? t('backend.planning_events.edit') : t('backend.planning_events.new')"
            v-on:close="eventForm.editModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="eventForm.submit(selectedPlanningId)">
                <p
                    v-if="eventForm.editModal.readOnly"
                    class="rounded-md border border-amber-500/30 bg-amber-500/10 p-2.5 text-xs text-amber-700 dark:text-amber-300"
                >
                    {{ t("backend.planning_events.errors.source_locked") }}
                </p>
                <AppInput
                    v-model="eventForm.editForm.title"
                    :label="t('backend.planning_events.fields.title')"
                    :placeholder="t('backend.planning_events.fields.titlePlaceholder')"
                    :error="eventForm.editModal.errors.title ?? ''"
                    :disabled="eventForm.editModal.readOnly"
                    :required="true"
                />
                <AppTextarea
                    v-model="eventForm.editForm.description"
                    :label="t('backend.planning_events.fields.description')"
                    :placeholder="t('backend.planning_events.fields.descriptionPlaceholder')"
                    :disabled="eventForm.editModal.readOnly"
                />
                <AppInput
                    v-model="eventForm.editForm.location"
                    :label="t('backend.planning_events.fields.location')"
                    :placeholder="t('backend.planning_events.fields.locationPlaceholder')"
                    :disabled="eventForm.editModal.readOnly"
                />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppDatePicker
                        v-model="eventForm.editForm.startAt"
                        :label="t('backend.planning_events.fields.startAt')"
                        :placeholder="t('backend.planning_events.fields.startAtPlaceholder')"
                        :error="eventForm.editModal.errors.startAt ?? ''"
                        :enable-time="!eventForm.editForm.allDay"
                    />
                    <AppDatePicker
                        v-model="eventForm.editForm.endAt"
                        :label="t('backend.planning_events.fields.endAt')"
                        :placeholder="t('backend.planning_events.fields.endAtPlaceholder')"
                        :error="eventForm.editModal.errors.endAt ?? ''"
                        :enable-time="!eventForm.editForm.allDay"
                    />
                </div>
                <div>
                    <AppFieldLabel :label="t('backend.planning_events.fields.allDay')" />
                    <AppCheckbox
                        v-model="eventForm.editForm.allDay"
                        :disabled="eventForm.editModal.readOnly"
                    />
                </div>
                <AppSelect
                    v-model="eventForm.editForm.status"
                    :label="t('backend.planning_events.fields.status')"
                    :options="statusOptions"
                    :disabled="eventForm.editModal.readOnly"
                />
                <AppMultiselect
                    v-model="eventForm.editForm.attendeeIds"
                    :label="t('backend.planning_events.fields.attendees')"
                    :placeholder="t('backend.planning_events.fields.attendeesPlaceholder')"
                    :options="peopleSidebar.userOptions.value"
                    :multiple="true"
                    :allow-empty="true"
                    :disabled="eventForm.editModal.readOnly"
                    open-direction="top"
                />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton
                        v-if="eventForm.editModal.event && !eventForm.editModal.readOnly && canManageEvents"
                        variant="danger"
                        size="md"
                        v-on:click="deletingEvent = eventForm.editModal.event"
                    >
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                    <span class="flex-1" />
                    <AppButton variant="ghost" size="md" v-on:click="eventForm.editModal.open = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton
                        v-if="!eventForm.editModal.readOnly && canManageEvents"
                        type="submit"
                        variant="primary"
                        size="md"
                        :loading="eventForm.editModal.saving"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete event confirm -->
        <AppModal :show="!!deletingEvent" max-width="sm" v-on:close="deletingEvent = null">
            <p class="text-sm text-primary">
                {{ t("backend.planning_events.delete_confirm", { title: deletingEvent?.title ?? "" }) }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingEvent = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeleteEvent">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.delete") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>

<style>
/* Calendar surface aligned with the Aurora theme tokens so it adapts to
   light / dark mode without hard-coded colours. */
.fc {
    --fc-border-color: var(--color-line);
    --fc-page-bg-color: transparent;
    --fc-neutral-bg-color: var(--color-surface-2);
    --fc-neutral-text-color: var(--color-secondary);
    --fc-today-bg-color: color-mix(in srgb, var(--color-accent-500) 10%, transparent);
    --fc-now-indicator-color: rgb(244 63 94); /* rose-500 */
    --fc-event-bg-color: var(--color-accent-500);
    --fc-event-border-color: var(--color-accent-500);
    --fc-event-text-color: #fff;
    --fc-button-bg-color: var(--color-surface-2);
    --fc-button-border-color: var(--color-line);
    --fc-button-text-color: var(--color-primary);
    --fc-button-hover-bg-color: var(--color-surface-3);
    --fc-button-hover-border-color: var(--color-line-strong);
    --fc-button-active-bg-color: color-mix(in srgb, var(--color-accent-500) 20%, transparent);
    --fc-button-active-border-color: var(--color-accent-500);
    color: var(--color-primary);
}

/* Toolbar */
.fc .fc-toolbar-title {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--color-primary);
}
.fc .fc-button {
    border-radius: 0.5rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    text-transform: none;
    box-shadow: none;
    transition: background-color 150ms, border-color 150ms, color 150ms;
}
.fc .fc-button:focus,
.fc .fc-button-primary:not(:disabled):focus {
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-accent-500) 35%, transparent);
}
.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active {
    color: var(--color-accent-500);
}

/* Day grid + columns */
.fc-theme-standard td,
.fc-theme-standard th,
.fc-theme-standard .fc-scrollgrid {
    border-color: var(--color-line);
}
.fc .fc-col-header-cell-cushion,
.fc .fc-daygrid-day-number,
.fc .fc-list-day-text,
.fc .fc-list-day-side-text {
    color: var(--color-secondary);
    font-weight: 500;
    text-decoration: none;
}
.fc .fc-day-other .fc-daygrid-day-number {
    opacity: 0.4;
}

/* Today highlight + now indicator */
.fc .fc-day-today {
    background-color: color-mix(in srgb, var(--color-accent-500) 8%, transparent) !important;
}
.fc .fc-day-today .fc-daygrid-day-number {
    color: var(--color-accent-500);
    font-weight: 600;
}

/* Business hours stay subtle */
.fc-business-hours,
.fc .fc-non-business {
    background-color: transparent !important;
}

/* Events */
.fc-event {
    cursor: pointer;
    border-radius: 0.375rem;
    padding: 1px 4px;
    border-width: 0;
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.06);
    transition: filter 120ms;
}
.fc-event:hover {
    filter: brightness(1.08);
}
.fc-h-event .fc-event-main,
.fc-v-event .fc-event-main {
    color: #fff;
    font-weight: 500;
}
.fc-event-cancelled {
    text-decoration: line-through;
    opacity: 0.55;
}
.fc-event-tentative {
    background-image: repeating-linear-gradient(
        45deg,
        rgb(255 255 255 / 0.22) 0,
        rgb(255 255 255 / 0.22) 4px,
        transparent 4px,
        transparent 8px
    );
    opacity: 0.85;
}

/* List view */
.fc .fc-list,
.fc .fc-list-table {
    border-color: var(--color-line);
}
.fc .fc-list-day-cushion {
    background-color: var(--color-surface-2);
}
.fc .fc-list-event:hover td {
    background-color: var(--color-surface-2);
}

/* Week numbers + popover */
.fc .fc-daygrid-week-number {
    background: transparent;
    color: var(--color-muted);
    font-weight: 500;
}
.fc .fc-popover {
    background: var(--color-surface);
    border-color: var(--color-line);
    color: var(--color-primary);
    box-shadow: 0 8px 24px rgb(0 0 0 / 0.18);
}
.fc .fc-popover-header {
    background: var(--color-surface-2);
    color: var(--color-primary);
}
</style>
