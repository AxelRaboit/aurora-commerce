import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Flip a recurring rule's `active` flag. When activating a rule whose
 * dayOfMonth already passed this month, the backend Manager.toggle()
 * generates the matching transaction immediately, so onToggled receives
 * the up-to-date rule (with bumped lastGeneratedAt).
 */
export function useRecurringToggle(togglePath, onToggled) {
    const { loading, request } = useRequest();

    async function toggle(rec) {
        const payload = await request(buildPath(togglePath, { id: rec.id }));
        if (!payload || payload.success === false || !payload.recurring) return;
        onToggled?.(payload.recurring);
    }

    return { loading, toggle };
}
