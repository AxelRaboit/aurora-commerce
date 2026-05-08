import { computed } from "vue";
import { useI18n } from "vue-i18n";

/**
 * Translated option lists for select inputs in the planning + event
 * forms. Computed so locale changes propagate.
 */

// Curated list of common timezones — covers Europe, Americas, Asia,
// Africa, Oceania. Static to keep the bundle light; if a less-common
// zone is needed, add it here or replace by an API-fed list.
const TIMEZONE_VALUES = [
    "Europe/Paris",
    "Europe/London",
    "Europe/Berlin",
    "Europe/Madrid",
    "Europe/Rome",
    "Europe/Amsterdam",
    "Europe/Brussels",
    "Europe/Lisbon",
    "Europe/Zurich",
    "Europe/Athens",
    "Europe/Moscow",
    "Africa/Casablanca",
    "Africa/Algiers",
    "Africa/Tunis",
    "Africa/Cairo",
    "Africa/Johannesburg",
    "America/New_York",
    "America/Chicago",
    "America/Denver",
    "America/Los_Angeles",
    "America/Toronto",
    "America/Sao_Paulo",
    "America/Mexico_City",
    "Asia/Dubai",
    "Asia/Tokyo",
    "Asia/Shanghai",
    "Asia/Singapore",
    "Asia/Hong_Kong",
    "Asia/Bangkok",
    "Asia/Kolkata",
    "Australia/Sydney",
    "Pacific/Auckland",
    "UTC",
];

export function usePlanningFormOptions() {
    const { t } = useI18n();

    const visibilityOptions = computed(() => [
        { value: "private", label: t("backend.plannings.visibility.private") },
        { value: "agency", label: t("backend.plannings.visibility.agency") },
        { value: "public", label: t("backend.plannings.visibility.public") },
    ]);

    const statusOptions = computed(() => [
        {
            value: "tentative",
            label: t("backend.planning_events.status.tentative"),
        },
        {
            value: "confirmed",
            label: t("backend.planning_events.status.confirmed"),
        },
        {
            value: "cancelled",
            label: t("backend.planning_events.status.cancelled"),
        },
    ]);

    const timezoneOptions = computed(() =>
        TIMEZONE_VALUES.map((value) => ({ value, label: value })),
    );

    return { visibilityOptions, statusOptions, timezoneOptions };
}
