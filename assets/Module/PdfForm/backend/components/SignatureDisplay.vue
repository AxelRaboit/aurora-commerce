<script setup>
import { useI18n } from "vue-i18n";
import { PenLine } from "lucide-vue-next";

const { t } = useI18n();

defineProps({
    src: { type: String, required: true },
});
</script>

<template>
    <div class="mt-4 p-3 rounded-lg border border-line bg-surface-2/40 flex items-center gap-4">
        <PenLine class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
        <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-muted uppercase tracking-wide mb-1">{{ t("backend.pdfform.documents.signatureTitle") }}</p>
            <img :src="src" :alt="t('backend.pdfform.documents.signatureTitle')" class="max-h-16 rounded border border-line bg-white" />
        </div>
    </div>
</template>
