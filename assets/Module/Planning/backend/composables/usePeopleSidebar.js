import { ref, computed, onMounted } from "vue";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * People sidebar — switches between "users" and "agencies" filtering
 * mode. In each mode, the user picks which entries to show; events are
 * then filtered against:
 *   - users     → event.attendees ∈ selectedUserIds
 *   - agencies  → event.planning.agency.id ∈ selectedAgencyIds
 *
 * Empty selection = show everything (no filter applied).
 *
 * Users come from the /selectable endpoint. Agencies are loaded from
 * /backend/agencies/selectable when the current user can access it
 * (ROLE_ADMIN). For everyone else, we silently fall back to the agencies
 * embedded in the loaded plannings — no extra permission needed, but
 * limited to agencies that already own at least one planning.
 */
export function usePeopleSidebar(
    usersSelectablePath,
    agenciesSelectablePath,
    planningsRef,
) {
    const { request } = useRequest();

    const mode = ref("users");
    const users = ref([]);
    const fetchedAgencies = ref([]);
    const selectedUserIds = ref(new Set());
    const selectedAgencyIds = ref(new Set());
    const searchQuery = ref("");

    const derivedAgencies = computed(() => {
        const seen = new Map();
        for (const planning of planningsRef.value) {
            const agency = planning.agency;
            if (agency && !seen.has(agency.id)) {
                seen.set(agency.id, { value: agency.id, label: agency.name });
            }
        }
        return [...seen.values()];
    });

    // Prefer the full /selectable list when available, otherwise fall back
    // to whatever agencies are referenced by the loaded plannings.
    const agencies = computed(() => {
        const list = fetchedAgencies.value.length
            ? fetchedAgencies.value
            : derivedAgencies.value;
        return [...list].sort((agencyA, agencyB) =>
            agencyA.label.localeCompare(agencyB.label),
        );
    });

    function matchesSearch(text) {
        const query = searchQuery.value.trim().toLowerCase();
        if ("" === query) return true;
        return text.toLowerCase().includes(query);
    }

    const filteredUsers = computed(() =>
        users.value.filter((user) => matchesSearch(user.name ?? "")),
    );

    const filteredAgencies = computed(() =>
        agencies.value.filter((agency) => matchesSearch(agency.label ?? "")),
    );

    async function loadUsers() {
        const data = await request(usersSelectablePath, null, HttpMethod.Get);
        if (data?.success) users.value = data.items ?? [];
    }

    async function loadAgencies() {
        const data = await request(
            agenciesSelectablePath,
            null,
            HttpMethod.Get,
        );
        if (data?.success) fetchedAgencies.value = data.items ?? [];
    }

    function toggleUser(userId) {
        const next = new Set(selectedUserIds.value);
        next.has(userId) ? next.delete(userId) : next.add(userId);
        selectedUserIds.value = next;
    }

    function toggleAgency(agencyId) {
        const next = new Set(selectedAgencyIds.value);
        next.has(agencyId) ? next.delete(agencyId) : next.add(agencyId);
        selectedAgencyIds.value = next;
    }

    function clearSelection() {
        if (mode.value === "users") selectedUserIds.value = new Set();
        else selectedAgencyIds.value = new Set();
    }

    /** Selects every item currently visible (i.e. matching the search). */
    function selectAllVisible() {
        if (mode.value === "users") {
            selectedUserIds.value = new Set(
                filteredUsers.value.map((user) => Number(user.id)),
            );
        } else {
            selectedAgencyIds.value = new Set(
                filteredAgencies.value.map((agency) => Number(agency.value)),
            );
        }
    }

    const hasFilter = computed(() => {
        if (mode.value === "users") return selectedUserIds.value.size > 0;
        return selectedAgencyIds.value.size > 0;
    });

    /** True when every visible entry is already selected — used to flip
     *  the "Tout sélectionner" toggle into a "Tout désélectionner". */
    const allVisibleSelected = computed(() => {
        if (mode.value === "users") {
            const list = filteredUsers.value;
            if (0 === list.length) return false;
            return list.every((user) =>
                selectedUserIds.value.has(Number(user.id)),
            );
        }
        const list = filteredAgencies.value;
        if (0 === list.length) return false;
        return list.every((agency) =>
            selectedAgencyIds.value.has(Number(agency.value)),
        );
    });

    // Reusable AppMultiselect-shape options — same source of truth as
    // the sidebar list, avoids duplicating the /selectable fetch.
    const userOptions = computed(() =>
        users.value.map((user) => ({
            value: Number(user.id),
            label: user.name,
        })),
    );

    /**
     * Returns a predicate `(event) => boolean` that the consumer can use
     * to filter the events list. When no filter is set, accepts everything.
     */
    function buildEventMatcher(planningsRef) {
        return (event) => {
            if (!hasFilter.value) return true;

            if (mode.value === "users") {
                if (!event.attendees?.length) return false;
                return event.attendees.some((attendee) =>
                    selectedUserIds.value.has(Number(attendee.id)),
                );
            }

            // agencies — look up the event's planning to get its agency
            const planning = planningsRef.value.find(
                (p) => Number(p.id) === Number(event.planningId),
            );
            const agencyId = planning?.agency?.id;
            if (!agencyId) return false;
            return selectedAgencyIds.value.has(Number(agencyId));
        };
    }

    onMounted(async () => {
        // Sequential — useRequest's loading guard rejects parallel calls,
        // so we await one before starting the next.
        await loadUsers();
        await loadAgencies();
    });

    return {
        mode,
        users,
        agencies,
        userOptions,
        filteredUsers,
        filteredAgencies,
        searchQuery,
        selectedUserIds,
        selectedAgencyIds,
        hasFilter,
        allVisibleSelected,
        toggleUser,
        toggleAgency,
        clearSelection,
        selectAllVisible,
        buildEventMatcher,
    };
}
