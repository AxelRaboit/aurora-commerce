<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { ScrollText } from "lucide-vue-next";

const props = defineProps({
    workflowTemplates: { type: Array, default: () => [] },
});

const { t } = useI18n();
const items = ref([...props.workflowTemplates]);
</script>

<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-accent-100 dark:bg-accent-900/30">
                <ScrollText class="w-6 h-6 text-accent-500" :stroke-width="1.5" />
            </div>
            <div>
                <h1 class="text-xl font-semibold text-primary">
                    {{ t("welding.workflow_templates.title") }}
                </h1>
                <p class="text-sm text-secondary">
                    {{ t("welding.workflow_templates.subtitle") }}
                </p>
            </div>
        </div>

        <div v-if="items.length === 0" class="rounded-xl border border-line bg-surface p-6 text-sm text-secondary text-center">
            {{ t("welding.workflow_templates.empty") }}
        </div>
        <ul v-else class="space-y-2">
            <li
                v-for="template in items"
                :key="template.id"
                class="rounded-lg border border-line bg-surface p-4 flex items-center gap-4"
            >
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-primary truncate">{{ template.title }}</div>
                    <div class="text-xs text-secondary">
                        v{{ template.version }} · {{ template.status }} · {{ template.stepsCount }}
                        {{ t("welding.workflow_templates.steps") }}
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
