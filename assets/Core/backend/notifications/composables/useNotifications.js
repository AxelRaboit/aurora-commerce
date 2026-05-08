import { ref, onMounted, onBeforeUnmount } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

const POLL_INTERVAL_MS = 30_000; // 30s — light enough not to hammer.

// Module-level singleton state. The notifications bell is mounted twice
// in the sidebar (one for the expanded layout, one for the collapsed
// layout) — without this singleton, each instance would fetch + poll
// independently, doubling the network traffic.
let sharedState = null;
let refCount = 0;
let pollTimer = null;

export function useNotifications(paths) {
    const { t } = useI18n();

    if (!sharedState) {
        sharedState = {
            entries: ref([]),
            unreadCount: ref(0),
            open: ref(false),
        };
    }
    const { entries, unreadCount, open } = sharedState;

    async function load() {
        try {
            const response = await fetch(paths.list, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data.success) {
                entries.value = data.entries ?? [];
                unreadCount.value = data.unreadCount ?? 0;
            }
        } catch {
            // silent — notifications are non-critical
        }
    }

    async function markRead(notification) {
        const url = buildPath(paths.markRead, { id: notification.id });
        try {
            await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
            });
            await load();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function markAllRead() {
        try {
            await fetch(paths.markAllRead, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
            });
            await load();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function deleteOne(notification) {
        const url = buildPath(paths.deletePath, { id: notification.id });
        try {
            await fetch(url, { method: HttpMethod.Delete });
            await load();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function deleteAll() {
        try {
            await fetch(paths.deleteAllPath, { method: HttpMethod.Delete });
            await load();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    function toggle() {
        open.value = !open.value;
        if (open.value) load();
    }

    onMounted(() => {
        // First mount triggers the initial fetch + the shared poll timer.
        // Subsequent mounts (e.g. the collapsed/expanded sidebar bells)
        // just join the existing ref-counted singleton.
        refCount += 1;
        if (1 === refCount) {
            load();
            pollTimer = setInterval(load, POLL_INTERVAL_MS);
        }
    });

    onBeforeUnmount(() => {
        refCount = Math.max(0, refCount - 1);
        if (0 === refCount && pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    });

    return {
        entries,
        unreadCount,
        open,
        toggle,
        load,
        markRead,
        markAllRead,
        deleteOne,
        deleteAll,
    };
}
