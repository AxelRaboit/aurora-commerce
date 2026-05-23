import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Flip a recurring rule's `active` flag. When activating a rule whose
 * dayOfMonth already passed this month, the backend Manager.toggle()
 * generates the matching transaction immediately, so onToggled receives
 * the up-to-date rule (with bumped lastGeneratedAt).
 *
 * Pausing is gated behind a confirmation modal — a paused rule stops
 * generating transactions silently and that's worth a confirm tap.
 * Resuming is harmless, so it fires immediately without a prompt.
 */
export function useRecurringToggle(togglePath, onToggled) {
    const { t } = useI18n();
    const { loading, request } = useRequest();
    const pendingPause = ref(null);

    async function performToggle(rec) {
        const wasActive = rec.active;
        const payload = await request(buildPath(togglePath, { id: rec.id }));
        if (!payload || payload.success === false || !payload.recurring) return;
        toast.success(
            t(
                wasActive
                    ? "personal_finance.recurring.toggle_paused"
                    : "personal_finance.recurring.toggle_resumed",
            ),
        );
        onToggled?.(payload.recurring);
    }

    function confirm(rec) {
        if (rec.active) {
            pendingPause.value = rec;
            return;
        }
        performToggle(rec);
    }

    async function submit() {
        if (!pendingPause.value) return;
        const target = pendingPause.value;
        pendingPause.value = null;
        await performToggle(target);
    }

    return { loading, pendingPause, confirm, submit };
}
