import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * State + HTTP for the Wallet Members modal :
 * fetches { members, invitations } from the list endpoint, exposes
 * mutation handlers that update the local collections in place after
 * each successful request — no full reload between actions.
 *
 * @param {object} paths
 * @param {string} paths.membersListPath        - GET   `__walletId__`
 * @param {string} paths.updateMemberRolePath   - POST  `__walletId__` + `__memberId__`
 * @param {string} paths.removeMemberPath       - POST  `__walletId__` + `__memberId__`
 * @param {string} paths.sendInvitationPath     - POST  `__walletId__`
 * @param {string} paths.revokeInvitationPath   - POST  `__walletId__` + `__invitationId__`
 * @param {string} paths.resendInvitationPath   - POST  `__walletId__` + `__invitationId__`
 */
export function useWalletMembers(paths) {
    const { t } = useI18n();
    const list = useRequest();
    const action = useRequest();

    const show = ref(false);
    const currentWallet = ref(null);
    const members = ref([]);
    const invitations = ref([]);
    const inviteForm = ref({ email: "", role: "viewer" });
    const inviteErrors = ref({});

    async function open(wallet) {
        currentWallet.value = wallet;
        members.value = [];
        invitations.value = [];
        inviteForm.value = { email: "", role: "viewer" };
        inviteErrors.value = {};
        show.value = true;
        if (!wallet?.id) return;
        const payload = await list.request(
            buildPath(paths.membersListPath, { walletId: wallet.id }),
            null,
            HttpMethod.Get,
        );
        if (payload && payload.success !== false) {
            members.value = payload.members ?? [];
            invitations.value = payload.invitations ?? [];
        }
    }

    function close() {
        show.value = false;
        currentWallet.value = null;
        members.value = [];
        invitations.value = [];
        inviteErrors.value = {};
    }

    async function changeRole(member, role) {
        if (!currentWallet.value || member.role === role) return;
        const url = buildPath(paths.updateMemberRolePath, {
            walletId: currentWallet.value.id,
            memberId: member.id,
        });
        const payload = await action.request(url, { role });
        if (!payload || payload.success === false || !payload.member) return;
        const idx = members.value.findIndex((m) => m.id === member.id);
        if (idx >= 0) members.value[idx] = payload.member;
        toast.success(t("personal_finance.wallets.members.role_updated"));
    }

    async function removeMember(member) {
        if (!currentWallet.value) return;
        const url = buildPath(paths.removeMemberPath, {
            walletId: currentWallet.value.id,
            memberId: member.id,
        });
        const payload = await action.request(url);
        if (!payload || payload.success === false) return;
        members.value = members.value.filter((m) => m.id !== member.id);
        toast.success(t("personal_finance.wallets.members.removed"));
    }

    async function sendInvitation() {
        if (!currentWallet.value) return;
        inviteErrors.value = {};
        const url = buildPath(paths.sendInvitationPath, { walletId: currentWallet.value.id });
        const payload = await action.request(url, inviteForm.value);
        if (!payload) return;
        if (payload.success === false) {
            inviteErrors.value = payload.errors ?? {};
            return;
        }
        if (payload.invitation) invitations.value.push(payload.invitation);
        inviteForm.value = { email: "", role: "viewer" };
        toast.success(t("personal_finance.wallets.members.invitation_sent"));
    }

    async function revokeInvitation(invitation) {
        if (!currentWallet.value) return;
        const url = buildPath(paths.revokeInvitationPath, {
            walletId: currentWallet.value.id,
            invitationId: invitation.id,
        });
        const payload = await action.request(url);
        if (!payload || payload.success === false) return;
        invitations.value = invitations.value.filter((i) => i.id !== invitation.id);
        toast.success(t("personal_finance.wallets.members.invitation_revoked"));
    }

    async function resendInvitation(invitation) {
        if (!currentWallet.value) return;
        const url = buildPath(paths.resendInvitationPath, {
            walletId: currentWallet.value.id,
            invitationId: invitation.id,
        });
        const payload = await action.request(url);
        if (!payload || payload.success === false) return;
        if (payload.invitation) {
            const idx = invitations.value.findIndex((i) => i.id === invitation.id);
            if (idx >= 0) invitations.value[idx] = payload.invitation;
        }
        toast.success(t("personal_finance.wallets.members.invitation_resent"));
    }

    return {
        show,
        currentWallet,
        members,
        invitations,
        inviteForm,
        inviteErrors,
        loading: list.loading,
        actionLoading: action.loading,
        open,
        close,
        changeRole,
        removeMember,
        sendInvitation,
        revokeInvitation,
        resendInvitation,
    };
}
