import { ref, computed, onMounted } from "vue";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
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
 * Users come from the /selectable endpoint. Agencies are derived from
 * the plannings list (each planning embeds its agency via the
 * serializer) — that avoids hitting /backend/agencies/selectable, which
 * is gated by ROLE_ADMIN, and means we only ever surface agencies that
 * actually have a planning attached.
 */
export function usePeopleSidebar(usersSelectablePath, planningsRef) {
    const { request } = useApiRequest();

    const mode = ref("users");
    const users = ref([]);
    const selectedUserIds = ref(new Set());
    const selectedAgencyIds = ref(new Set());
    const searchQuery = ref("");

    const agencies = computed(() => {
        const seen = new Map();
        for (const planning of planningsRef.value) {
            const agency = planning.agency;
            if (agency && !seen.has(agency.id)) {
                seen.set(agency.id, { value: agency.id, label: agency.name });
            }
        }
        return [...seen.values()].sort((agencyA, agencyB) =>
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

    const hasFilter = computed(() => {
        if (mode.value === "users") return selectedUserIds.value.size > 0;
        return selectedAgencyIds.value.size > 0;
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

    onMounted(() => {
        loadUsers();
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
        toggleUser,
        toggleAgency,
        clearSelection,
        buildEventMatcher,
    };
}
