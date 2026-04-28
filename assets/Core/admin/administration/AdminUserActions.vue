<script setup>
import { useI18n } from "vue-i18n";
import { LogIn, Pencil, Shield, Trash2, UserRound } from "lucide-vue-next";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";

const { t } = useI18n();

const props = defineProps({
    user: { type: Object, required: true },
    impersonatePath: { type: String, required: true },
});

const emit = defineEmits(["edit", "toggle-role", "delete"]);
</script>

<template>
    <AppIconButton color="accent" :title="t('admin.users.edit')" v-on:click="emit('edit', props.user)">
        <Pencil class="w-4 h-4" :stroke-width="2" />
    </AppIconButton>

    <AppIconButton
        v-if="!user.isCurrent"
        color="amber"
        :href="impersonatePath.replace('__email__', encodeURIComponent(user.email))"
        :title="t('admin.users.impersonate', { name: user.name })"
    >
        <LogIn class="w-4 h-4" :stroke-width="2" />
    </AppIconButton>

    <AppIconButton
        v-if="!user.isCurrent"
        :color="user.isDevRole ? 'accent' : 'rose'"
        :title="user.isDevRole ? t('admin.users.revoke_dev') : t('admin.users.grant_dev')"
        v-on:click="emit('toggle-role', props.user)"
    >
        <component :is="user.isDevRole ? UserRound : Shield" class="w-4 h-4" :stroke-width="2" />
    </AppIconButton>

    <AppIconButton
        v-if="!user.isCurrent"
        color="rose"
        :title="t('shared.common.delete')"
        v-on:click="emit('delete', props.user)"
    >
        <Trash2 class="w-4 h-4" :stroke-width="2" />
    </AppIconButton>
</template>
