import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/useRequest.js";
import { useServerErrors } from "@/shared/composables/form/useServerErrors.js";
import {
    required,
    email as emailValidator,
} from "@/shared/utils/validation/validators.js";
import { emptyContactForm } from "./useContactsCreate.js";

export function useContactsEdit(updatePath, reset) {
    const { t } = useI18n();

    const showEdit = ref(false);
    const editingContact = ref(null);
    const editForm = ref(emptyContactForm());
    const { errors: editErrors, validate, clearErrors, handleErrors } = useServerErrors();
    const { loading: editLoading, request } = useRequest();

    function openEdit(contact) {
        editingContact.value = contact;
        editForm.value = {
            firstName: contact.firstName,
            lastName: contact.lastName,
            email: contact.email ?? "",
            phone: contact.phone ?? "",
            company: contact.company ?? "",
            notes: contact.notes ?? "",
        };
        clearErrors();
        showEdit.value = true;
    }

    async function submitEdit() {
        if (
            !validate({
                firstName: () =>
                    required(
                        t("backend.crm.contacts.errors.first_name_required"),
                    )(editForm.value.firstName),
                lastName: () =>
                    required(
                        t("backend.crm.contacts.errors.last_name_required"),
                    )(editForm.value.lastName),
                email: () =>
                    editForm.value.email
                        ? emailValidator(
                              t("backend.crm.contacts.errors.email_invalid"),
                          )(editForm.value.email)
                        : null,
            })
        )
            return;
        const url = buildPath(updatePath, { id: editingContact.value.id });
        const data = await request(url, editForm.value);
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(t("backend.crm.contacts.updated"));
            reset();
        } else handleErrors(data.errors);
    }

    return {
        showEdit,
        editingContact,
        editForm,
        editErrors,
        editLoading,
        openEdit,
        submitEdit,
    };
}
