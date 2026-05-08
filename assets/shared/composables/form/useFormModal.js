import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * Manages create/edit modal state with integrated fetch submit.
 * Handles loading, error mapping, and success/error toasts.
 */
export function useFormModal() {
    const { t } = useI18n();
    const modal = reactive({
        open: false,
        editing: null,
        errors: {},
        saving: false,
    });

    function openCreate(resetFn) {
        modal.editing = null;
        modal.errors = {};
        resetFn?.();
        modal.open = true;
    }

    function openEdit(item, populateFn) {
        modal.editing = item;
        modal.errors = {};
        populateFn?.(item);
        modal.open = true;
    }

    function close() {
        modal.open = false;
        modal.errors = {};
    }

    async function submit(url, payload, onSuccess) {
        modal.saving = true;
        modal.errors = {};
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!data.success) {
                modal.errors = data.errors ?? {};
                return;
            }
            modal.open = false;
            toast.success(t("shared.common.saved"));
            onSuccess?.(data);
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            modal.saving = false;
        }
    }

    return { modal, openCreate, openEdit, close, submit };
}
