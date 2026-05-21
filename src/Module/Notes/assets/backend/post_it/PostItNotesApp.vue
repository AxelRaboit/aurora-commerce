<script setup>
import { useI18n } from "vue-i18n";

const { t } = useI18n();
</script>

<template>
    <div class="p-6">
        <h1 class="text-xl font-semibold text-primary">{{ t("notes.post_it.title") }}</h1>
        <p class="mt-2 text-muted">{{ t("notes.post_it.placeholder") }}</p>
    </div>
</template>
