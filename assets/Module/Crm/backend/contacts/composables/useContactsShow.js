import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

const ACTIVITY_KEYS = Object.freeze({
    "contact.created": "backend.crm.activity.created",
    "contact.updated": "backend.crm.activity.updated",
    "contact.deleted": "backend.crm.activity.deleted",
});

export function useContactsShow(activityPath) {
    const { t } = useI18n();
    const showShow = ref(false);
    const showingContact = ref(null);
    const activity = ref([]);
    const { loading: activityLoading, request } = useRequest();

    async function openShow(contact) {
        showingContact.value = contact;
        activity.value = [];
        showShow.value = true;

        if (!activityPath) return;
        const data = await request(
            buildPath(activityPath, { id: contact.id }),
            null,
            { method: HttpMethod.Get },
        );
        if (data?.success) {
            activity.value = data.items ?? [];
        }
    }

    function closeShow() {
        showShow.value = false;
        showingContact.value = null;
        activity.value = [];
    }

    function activityActionLabel(action) {
        const key = ACTIVITY_KEYS[action];
        return key ? t(key) : action;
    }

    return {
        showShow,
        showingContact,
        activity,
        activityLoading,
        openShow,
        closeShow,
        activityActionLabel,
    };
}

export function contactSourceColor(source) {
    if (source === "manual") return "gray";
    if (source === "form") return "sky";
    if (source === "order") return "emerald";
    return "gray";
}
