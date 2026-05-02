<script setup>
import { useI18n } from "vue-i18n";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";

const { t } = useI18n();

defineProps({
    user: { type: Object, required: true },
});
</script>

<template>
    <AppBadge v-if="user.isCurrent" color="accent">{{ t("admin.users.you") }}</AppBadge>
    <AppBadge :color="user.isDevRole ? 'accent' : 'gray'">
        {{ user.isDevRole ? t("admin.users.role_dev") : t("admin.users.role_user") }}
    </AppBadge>
    <AppBadge color="gray" class="uppercase">{{ user.locale }}</AppBadge>
</template>
