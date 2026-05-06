import { reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useUsersInvite(invitePath, roles, fetchUsers) {
    const { t } = useI18n();
    const inviteModal = reactive({ open: false, errors: {}, saving: false });
    const inviteForm = reactive({
        name: "",
        email: "",
        role: roles[0]?.value ?? "",
        message: "",
    });

    function openInvite() {
        inviteModal.errors = {};
        inviteForm.name = "";
        inviteForm.email = "";
        inviteForm.role = roles[0]?.value ?? "";
        inviteForm.message = "";
        inviteModal.open = true;
    }

    async function submitInvite() {
        inviteModal.saving = true;
        inviteModal.errors = {};
        try {
            const response = await fetch(invitePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(inviteForm),
            });
            const data = await response.json();
            if (!data.success) {
                inviteModal.errors = data.errors ?? {};
                return;
            }
            toast.success(t("backend.users.invitationSent"));
            inviteModal.open = false;
            fetchUsers();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            inviteModal.saving = false;
        }
    }

    return { inviteModal, inviteForm, openInvite, submitInvite };
}
