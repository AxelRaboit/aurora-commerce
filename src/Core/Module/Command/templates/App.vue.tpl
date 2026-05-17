<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="p-6 space-y-2">
        <h1 class="text-xl font-semibold">{{ t('{{MODULE_ID}}.title') }}</h1>
        <p class="text-muted">{{ t('{{MODULE_ID}}.subtitle') }}</p>
    </div>
</template>
