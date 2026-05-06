import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { UserStatus } from "@core/utils/enums/user/userStatus.js";

export function useUsersActions(props, fetchUsers) {
    const { t } = useI18n();

    // ── View ─────────────────────────────────────────────────────────────────
    const viewingUser = ref(null);

    async function openView(user) {
        viewingUser.value = { ...user, subordinates: [], subordinatesCount: 0 };
        try {
            const response = await fetch(
                buildPath(props.showPath, { id: user.id }),
            );
            const data = await response.json();
            if (data.success && viewingUser.value?.id === user.id)
                viewingUser.value = data.user;
        } catch {
            /* fail silent — row data already visible */
        }
    }

    // ── Resend invitation ─────────────────────────────────────────────────────
    async function resendInvitation(user) {
        try {
            const response = await fetch(
                buildPath(props.resendInvitationPath, { id: user.id }),
                { method: HttpMethod.Post },
            );
            const data = await response.json();
            if (data.success) {
                toast.success(t("backend.users.invitationResent"));
                fetchUsers();
            } else toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    // ── Toggle disabled ───────────────────────────────────────────────────────
    const togglingUser = ref(null);

    function askToggleDisabled(user) {
        togglingUser.value = user;
    }

    async function confirmToggleDisabled() {
        const user = togglingUser.value;
        if (!user) return;
        try {
            const response = await fetch(
                buildPath(props.toggleDisabledPath, { id: user.id }),
                { method: HttpMethod.Post },
            );
            const data = await response.json();
            if (data.success) {
                toast.success(t("shared.common.saved"));
                fetchUsers();
            } else toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            togglingUser.value = null;
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    const deletingUser = ref(null);

    async function confirmDelete() {
        const user = deletingUser.value;
        if (!user) return;
        try {
            const response = await fetch(
                buildPath(props.deletePath, { id: user.id }),
                { method: HttpMethod.Post },
            );
            const data = await response.json();
            if (data.success) {
                toast.success(t("shared.common.deleted"));
                fetchUsers();
            } else toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            deletingUser.value = null;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function statusBadgeColor(status) {
        if ("active" === status) return "emerald";
        if ("invited" === status) return "amber";
        return "rose";
    }

    const isCurrent = (user) => user.id === props.currentUserId;
    const canActOn = (user) =>
        !isCurrent(user) && props.currentUserPriority >= user.rolePriority;

    return {
        viewingUser,
        openView,
        resendInvitation,
        togglingUser,
        askToggleDisabled,
        confirmToggleDisabled,
        deletingUser,
        confirmDelete,
        statusBadgeColor,
        isCurrent,
        canActOn,
        UserStatus,
    };
}
