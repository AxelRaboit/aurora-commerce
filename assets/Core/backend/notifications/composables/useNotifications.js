import { ref, onMounted, onBeforeUnmount } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

const POLL_INTERVAL_MS = 30_000; // 30s — light enough not to hammer.

export function useNotifications(paths) {
    const { t } = useI18n();
    const entries = ref([]);
    const unreadCount = ref(0);
    const open = ref(false);

    let pollTimer = null;

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
        load();
        pollTimer = setInterval(load, POLL_INTERVAL_MS);
    });

    onBeforeUnmount(() => {
        if (pollTimer) clearInterval(pollTimer);
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
