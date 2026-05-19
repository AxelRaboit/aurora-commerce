<script setup>
import { useI18n } from 'vue-i18n';

const props = defineProps({
    score: { type: Number, required: true },
    strengthLabel: { type: String, default: '' },
    strengthColor: { type: String, default: '' },
});

const { t } = useI18n();
</script>

<template>
    <div class="space-y-1 px-0.5">
        <div class="flex gap-1">
            <div
                v-for="i in 5"
                :key="i"
                class="h-1 flex-1 rounded-full transition-all duration-300"
                :class="i <= score ? strengthColor : 'bg-line'"
            />
        </div>
        <p v-if="strengthLabel" class="text-xs text-secondary">{{ t(strengthLabel) }}</p>
    </div>
</template>
