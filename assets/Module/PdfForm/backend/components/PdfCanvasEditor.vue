<script setup>
import { ref, toRef, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { usePdfRenderer } from "../composables/usePdfRenderer.js";
import { usePdfFieldInteraction } from "../composables/usePdfFieldInteraction.js";

const { t } = useI18n();

const props = defineProps({
    pdfUrl: { type: String, default: null },
    fieldPositions: { type: Object, default: () => ({}) },
    fieldValues: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["update:fieldValues", "field-focus"]);

const containerRef = ref(null);

const { pages, loading, setCanvasRef, load, currentUrl } = usePdfRenderer(
    containerRef,
    toRef(props, "fieldPositions"),
);

const { updateField, isChecked } = usePdfFieldInteraction(
    toRef(props, "fieldValues"),
    emit,
);

onMounted(() => {
    if (props.pdfUrl) load(props.pdfUrl);
});

watch(() => props.pdfUrl, (url) => {
    if (url && url !== currentUrl.get()) load(url);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full h-full overflow-y-auto scrollbar-thin">
        <div v-if="loading" class="absolute inset-0 z-20 flex items-center justify-center bg-surface/70 backdrop-blur-sm rounded-lg">
            <div class="flex items-center gap-2 text-sm text-muted">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                </svg>
                {{ t("backend.pdfform.documents.previewUpdating") }}
            </div>
        </div>

        <div class="flex flex-col items-center gap-4 p-4">
            <div
                v-for="(p, i) in pages"
                :key="p.pageNum"
                class="relative shadow-xl rounded border border-line/30"
                :style="{ width: p.width + 'px', height: p.height + 'px' }"
            >
                <canvas :ref="(el) => setCanvasRef(el, i)" :width="p.width" :height="p.height" class="block rounded" />

                <template v-for="o in p.overlays" :key="o.fieldName">
                    <input
                        v-if="o.fieldType === 'checkbox'"
                        type="checkbox"
                        :checked="isChecked(o.fieldName)"
                        class="absolute cursor-pointer accent-accent"
                        :style="{ left: o.left + 'px', top: o.top + 'px', width: o.width + 'px', height: o.height + 'px' }"
                        v-on:change="updateField(o.fieldName, $event.target.checked ? 'Yes' : 'Off')"
                        v-on:focus="emit('field-focus', o.fieldName)"
                    >
                    <input
                        v-else-if="o.fieldType === 'radio'"
                        type="radio"
                        :name="o.radioGroupName"
                        :value="o.optionValue"
                        :checked="fieldValues[o.radioGroupName] === o.optionValue"
                        class="absolute cursor-pointer accent-accent"
                        :style="{ left: o.left + 'px', top: o.top + 'px', width: o.width + 'px', height: o.height + 'px' }"
                        v-on:change="updateField(o.radioGroupName, o.optionValue)"
                        v-on:focus="emit('field-focus', o.radioGroupName)"
                    >
                    <input
                        v-else
                        type="text"
                        :value="fieldValues[o.fieldName] ?? ''"
                        class="absolute bg-accent/8 border border-accent/30 hover:border-accent/60 hover:bg-accent/12 focus:border-accent focus:bg-white focus:ring-1 focus:ring-accent/50 rounded-sm outline-none text-gray-900 transition-all duration-100"
                        :style="{
                            left: o.left + 'px',
                            top: o.top + 'px',
                            width: o.width + 'px',
                            height: o.height + 'px',
                            fontSize: o.fontSize + 'px',
                            lineHeight: o.height + 'px',
                            padding: '0 3px',
                        }"
                        v-on:input="updateField(o.fieldName, $event.target.value)"
                        v-on:focus="emit('field-focus', o.fieldName)"
                    >
                </template>
            </div>
        </div>
    </div>
</template>
