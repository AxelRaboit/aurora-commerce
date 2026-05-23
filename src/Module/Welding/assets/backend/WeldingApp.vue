<script setup>
import { useI18n } from "vue-i18n";
import { Flame } from "lucide-vue-next";

const { t } = useI18n();
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-accent-100 dark:bg-accent-900/30">
                <Flame class="w-6 h-6 text-accent-500" :stroke-width="1.5" />
            </div>
            <div>
                <h1 class="text-xl font-semibold text-primary">{{ t("welding.title") }}</h1>
                <p class="text-sm text-secondary">{{ t("welding.subtitle") }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-line bg-surface p-6 text-sm text-secondary">
            {{ t("welding.placeholder") }}
        </div>
    </div>
</template>
