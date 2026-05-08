import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";

const DEFAULT_FORM = {
    title: "",
    description: "",
    location: "",
    startAt: "",
    endAt: "",
    allDay: false,
    status: "confirmed",
    attendeeIds: [],
};

export function useEventForm(events, createPath, updatePath, deletePath) {
    const { t } = useI18n();
    const { request } = useApiRequest();

    const editModal = reactive({
        open: false,
        event: null,
        errors: {},
        saving: false,
        readOnly: false,
    });
    const editForm = reactive({ ...DEFAULT_FORM });

    function resetForm() {
        Object.assign(editForm, DEFAULT_FORM);
    }

    function openCreate(slot) {
        editModal.event = null;
        editModal.errors = {};
        editModal.readOnly = false;
        resetForm();
        if (slot) {
            editForm.startAt = slot.startStr ?? slot.start;
            editForm.endAt = slot.endStr ?? slot.end;
            editForm.allDay = !!slot.allDay;
        }
        editModal.open = true;
    }

    function openEdit(event) {
        editModal.event = event;
        editModal.errors = {};
        editModal.readOnly = !event.editable;
        Object.assign(editForm, {
            title: event.title,
            description: event.description ?? "",
            location: event.location ?? "",
            startAt: event.startAt,
            endAt: event.endAt,
            allDay: event.allDay,
            status: event.status,
            attendeeIds: event.attendees.map((attendee) => attendee.id),
        });
        editModal.open = true;
    }

    async function submit(planningId) {
        if (editModal.readOnly) return;
        editModal.saving = true;
        editModal.errors = {};
        try {
            const isCreate = null === editModal.event;
            const url = isCreate
                ? buildPath(createPath, { id: planningId })
                : buildPath(updatePath, { eventId: editModal.event.id });
            const data = await request(url, { ...editForm });
            if (!data?.success) {
                editModal.errors = data?.errors ?? {};
                return;
            }
            if (isCreate) {
                events.value.push(data.event);
            } else {
                const index = events.value.findIndex(
                    (event) => event.id === editModal.event.id,
                );
                if (index !== -1) events.value[index] = data.event;
            }
            toast.success(t("shared.common.saved"));
            editModal.open = false;
        } finally {
            editModal.saving = false;
        }
    }

    async function remove() {
        if (editModal.readOnly || null === editModal.event) return;
        if (!confirm(t("backend.planning_events.delete_confirm"))) return;
        editModal.saving = true;
        try {
            const url = buildPath(deletePath, { eventId: editModal.event.id });
            const data = await request(url, {});
            if (!data?.success) {
                toast.error(data?.errors?._global ?? t("shared.common.error"));
                return;
            }
            events.value = events.value.filter(
                (event) => event.id !== editModal.event.id,
            );
            toast.success(t("shared.common.deleted"));
            editModal.open = false;
        } finally {
            editModal.saving = false;
        }
    }

    return { editModal, editForm, openCreate, openEdit, submit, remove };
}
