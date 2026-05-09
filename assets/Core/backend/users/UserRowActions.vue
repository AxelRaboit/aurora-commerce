<script setup>
import { Eye, Mail, Pencil, Trash2, Power, LogIn, ShieldCheck } from "lucide-vue-next";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";

const { t } = useI18n();

const props = defineProps({
    user: { type: Object, required: true },
    isDev: { type: Boolean, default: false },
    canAct: { type: Boolean, required: true },
    canEdit: { type: Boolean, default: false },
    hasPrivileges: { type: Boolean, default: false },
    impersonatePath: { type: String, default: "" },
    impersonateFrontPath: { type: String, default: "" },
});

const emit = defineEmits(["view", "resend", "edit", "privileges", "toggle-disabled", "delete"]);
</script>

<template>
    <div class="flex items-center gap-0.5">
        <AppIconButton
            color="sky"
            :title="t('backend.users.view')"
            v-on:click="emit('view', user)"
        >
            <Eye class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
        <AppIconButton
            v-if="user.status === 'invited' && canAct"
            color="amber"
            :title="t('backend.users.resendInvitation')"
            v-on:click="emit('resend', user)"
        >
            <Mail class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
        <AppIconButton
            v-if="isDev && canAct && user.type === 'backend'"
            color="amber"
            :title="t('backend.users.impersonate', { name: user.name })"
            :href="buildPath(impersonatePath, { email: user.email })"
        >
            <LogIn class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
        <AppIconButton
            v-if="isDev && user.type === 'frontend' && impersonateFrontPath"
            color="violet"
            :title="t('backend.users.impersonateFront', { name: user.name })"
            :href="buildPath(impersonateFrontPath, { id: user.id })"
        >
            <LogIn class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
        <AppIconButton
            v-if="canAct || canEdit"
            color="accent"
            :title="t('shared.common.edit')"
            v-on:click="emit('edit', user)"
        >
            <Pencil class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
        <AppIconButton
            v-if="isDev && canAct && hasPrivileges && !user.isDev"
            color="accent"
            :title="t('backend.users.privileges.title')"
            v-on:click="emit('privileges', user)"
        >
            <ShieldCheck class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
        <AppIconButton
            v-if="canAct"
            color="amber"
            :title="user.status === 'disabled' ? t('backend.users.enable') : t('backend.users.disable')"
            v-on:click="emit('toggle-disabled', user)"
        >
            <Power class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
        <AppIconButton
            v-if="canAct"
            color="rose"
            :title="t('shared.common.delete')"
            v-on:click="emit('delete', user)"
        >
            <Trash2 class="w-4 h-4" :stroke-width="2" />
        </AppIconButton>
    </div>
</template>
