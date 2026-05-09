import { computed } from "vue";

export function usePlanningPrivileges(can) {
    const canCreatePlannings = computed(() => can("planning.plannings.create"));
    const canEditPlannings = computed(() => can("planning.plannings.edit"));
    const canDeletePlannings = computed(() => can("planning.plannings.delete"));
    const canManagePlannings = computed(
        () =>
            canCreatePlannings.value ||
            canEditPlannings.value ||
            canDeletePlannings.value,
    );
    const canCreateEvents = computed(() => can("planning.events.create"));
    const canEditEvents = computed(() => can("planning.events.edit"));
    const canDeleteEvents = computed(() => can("planning.events.delete"));
    const canManageEvents = computed(
        () =>
            canCreateEvents.value ||
            canEditEvents.value ||
            canDeleteEvents.value,
    );

    return {
        canCreatePlannings,
        canEditPlannings,
        canDeletePlannings,
        canManagePlannings,
        canCreateEvents,
        canEditEvents,
        canDeleteEvents,
        canManageEvents,
    };
}
