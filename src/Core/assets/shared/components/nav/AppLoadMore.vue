<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/action/AppButton.vue";

defineProps({
    hasMore: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    label: { type: String, default: "" },
});

defineEmits(["load"]);

const { t } = useI18n();
</script>

<template>
    <AppButton
        v-if="hasMore"
        variant="ghost"
        size="sm"
        class="w-full"
        :loading="loading"
        v-on:click="$emit('load')"
    >
        {{ label || t('shared.common.load_more') }}
    </AppButton>
</template>
