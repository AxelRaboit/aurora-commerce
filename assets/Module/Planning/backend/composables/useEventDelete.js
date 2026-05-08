import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";

export function useEventDelete(events, deletePath, options = {}) {
    const { t } = useI18n();
    const { request } = useApiRequest();

    const deletingEvent = ref(null);

    async function confirmDelete() {
        if (!deletingEvent.value) return;
        const data = await request(
            buildPath(deletePath, { eventId: deletingEvent.value.id }),
        );
        if (!data?.success) {
            toast.error(data?.errors?._global ?? t("shared.common.error"));
            return;
        }

        events.value = events.value.filter(
            (event) => event.id !== deletingEvent.value.id,
        );
        toast.success(t("shared.common.deleted"));
        deletingEvent.value = null;
        options.onDeleted?.();
    }

    return { deletingEvent, confirmDelete };
}
