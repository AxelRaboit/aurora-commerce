<script setup>
import { computed, defineExpose, ref } from "vue";
import { useI18n } from "vue-i18n";
import { Users, Mail, X, Trash2, Send, RefreshCw, AlertCircle } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useWalletMembers } from "../composables/useWalletMembers.js";

const props = defineProps({
    membersListPath: { type: String, required: true },
    updateMemberRolePath: { type: String, required: true },
    removeMemberPath: { type: String, required: true },
    sendInvitationPath: { type: String, required: true },
    revokeInvitationPath: { type: String, required: true },
    resendInvitationPath: { type: String, required: true },
    /** Roles assignable via invitation (Owner is excluded, transferred separately). */
    roles: { type: Array, required: true },
});

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const {
    show,
    currentWallet,
    members,
    invitations,
    inviteForm,
    inviteErrors,
    loading,
    actionLoading,
    open,
    close,
    changeRole,
    removeMember,
    sendInvitation,
    revokeInvitation,
    resendInvitation,
} = useWalletMembers(props);

const roleOptions = computed(() =>
    props.roles.map((role) => ({ value: role, label: t(`personal_finance.wallets.roles.${role}`) })),
);

// Role select for an existing member also lets owners transfer — for V1 we only
// expose Editor/Viewer in the dropdown (Owner row is read-only). The Owner row
// is identified by its role === 'owner'.
const memberRoleOptions = roleOptions;

const pendingRemove = ref(null);

function confirmRemove(member) {
    pendingRemove.value = member;
}

async function doRemove() {
    if (!pendingRemove.value) return;
    await removeMember(pendingRemove.value);
    pendingRemove.value = null;
}

defineExpose({ open });
</script>

<template>
    <AppModal
        :show="show"
        max-width="2xl"
        :title="t('personal_finance.wallets.members.modal_title', { name: currentWallet?.name ?? '' })"
        :icon="Users"
        :closeable="false"
        v-on:close="close"
    >
        <div class="relative space-y-6">
            <!-- Members list -->
            <section>
                <header class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-medium uppercase tracking-wider text-muted">
                        {{ t("personal_finance.wallets.members.section_members") }}
                    </h3>
                    <span class="text-xs text-muted">{{ members.length }}</span>
                </header>

                <ul v-if="members.length" class="divide-y divide-line/40 border border-line rounded-md overflow-hidden">
                    <li v-for="member in members" :key="member.id" class="px-3 py-2 flex items-center gap-3 bg-surface">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-primary truncate">{{ member.userName }}</p>
                            <p class="text-xs text-muted truncate">{{ member.userEmail }}</p>
                        </div>
                        <div class="shrink-0 w-32">
                            <AppMultiselect
                                v-if="member.role !== 'owner'"
                                :model-value="member.role"
                                :options="memberRoleOptions"
                                :allow-empty="false"
                                v-on:update:model-value="(role) => changeRole(member, role)"
                            />
                            <span v-else class="inline-block px-2 py-1 text-xs rounded bg-emerald-500/15 text-emerald-400">
                                {{ t("personal_finance.wallets.roles.owner") }}
                            </span>
                        </div>
                        <AppIconButton
                            v-if="member.role !== 'owner'"
                            color="rose"
                            :title="t('personal_finance.wallets.members.remove')"
                            v-on:click="confirmRemove(member)"
                        >
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </li>
                </ul>
                <p v-else-if="!loading" class="text-sm text-muted text-center py-3">
                    {{ t("personal_finance.wallets.members.empty_members") }}
                </p>
            </section>

            <!-- Pending invitations -->
            <section>
                <header class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-medium uppercase tracking-wider text-muted">
                        {{ t("personal_finance.wallets.members.section_invitations") }}
                    </h3>
                    <span class="text-xs text-muted">{{ invitations.length }}</span>
                </header>

                <ul v-if="invitations.length" class="divide-y divide-line/40 border border-line rounded-md overflow-hidden">
                    <li v-for="inv in invitations" :key="inv.id" class="px-3 py-2 flex items-center gap-3 bg-surface" :class="inv.isExpired ? 'opacity-60' : ''">
                        <Mail class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-primary truncate">{{ inv.email }}</p>
                            <p class="text-xs text-muted">
                                {{ t(`personal_finance.wallets.roles.${inv.role}`) }} ·
                                {{ t("personal_finance.wallets.members.invitation_expires", { date: formatDateShort(inv.expiresAt) }) }}
                                <span v-if="inv.isExpired" class="ml-1 inline-flex items-center gap-1 text-amber-400">
                                    <AlertCircle class="w-3 h-3" :stroke-width="2" />
                                    {{ t("personal_finance.wallets.invitations.expired") }}
                                </span>
                            </p>
                        </div>
                        <AppIconButton color="accent" :title="t('personal_finance.wallets.members.invitation_resend')" v-on:click="resendInvitation(inv)">
                            <RefreshCw class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="rose" :title="t('personal_finance.wallets.members.invitation_revoke')" v-on:click="revokeInvitation(inv)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </li>
                </ul>
                <p v-else-if="!loading" class="text-sm text-muted text-center py-3">
                    {{ t("personal_finance.wallets.members.empty_invitations") }}
                </p>
            </section>

            <!-- Send invitation form -->
            <section class="border-t border-line pt-4">
                <h3 class="text-xs font-medium uppercase tracking-wider text-muted mb-2">
                    {{ t("personal_finance.wallets.members.section_invite") }}
                </h3>
                <AppMessage variant="info" class="mb-3">
                    {{ t("personal_finance.wallets.members.invite_help") }}
                </AppMessage>
                <form class="flex flex-col sm:flex-row gap-2" v-on:submit.prevent="sendInvitation">
                    <AppInput
                        v-model="inviteForm.email"
                        type="email"
                        :label="t('personal_finance.wallets.members.fields.email')"
                        :placeholder="t('personal_finance.wallets.members.placeholders.email')"
                        :error="inviteErrors.email"
                        class="flex-1"
                        required
                    />
                    <AppMultiselect
                        v-model="inviteForm.role"
                        :label="t('personal_finance.wallets.members.fields.role')"
                        :options="roleOptions"
                        :allow-empty="false"
                        :error="inviteErrors.role"
                        class="sm:w-40"
                        required
                    />
                </form>
                <div class="flex justify-end mt-3">
                    <AppButton variant="primary" size="md" :loading="actionLoading" v-on:click="sendInvitation">
                        <Send class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("personal_finance.wallets.members.send_invitation") }}
                    </AppButton>
                </div>
            </section>

            <AppLoader :active="loading" />
        </div>

        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="close">
                    <X class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.close") }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>

    <AppModal
        :show="!!pendingRemove"
        max-width="sm"
        :closeable="false"
        :title="t('personal_finance.wallets.members.remove')"
        :icon="Trash2"
        v-on:close="pendingRemove = null"
    >
        <p class="text-sm text-primary">{{ t("personal_finance.wallets.members.remove_confirm", { name: pendingRemove?.userName ?? '' }) }}</p>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingRemove = null">
                    <X class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("shared.common.cancel") }}
                </AppButton>
                <AppButton variant="danger" size="md" :loading="actionLoading" v-on:click="doRemove">
                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("personal_finance.wallets.members.remove") }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
