<script setup>
import { ref, watch, computed } from "vue";
import AppFieldLabel from "@/shared/components/form/AppFieldLabel.vue";
import AppColorSwatch from "@/shared/components/form/picker/AppColorSwatch.vue";
import { X } from "lucide-vue-next";

const DEFAULT_PRESETS = [
    "#ef4444", "#f97316", "#f59e0b", "#eab308",
    "#84cc16", "#22c55e", "#10b981", "#14b8a6",
    "#06b6d4", "#3b82f6", "#6366f1", "#8b5cf6",
    "#a855f7", "#ec4899", "#f43f5e", "#64748b",
];

const presets = computed(() => {
    const fromConfig = typeof window !== "undefined"
        ? window.__auroraConfig?.colorPickerPresets
        : null;
    return Array.isArray(fromConfig) && fromConfig.length > 0
        ? fromConfig
        : DEFAULT_PRESETS;
});

const props = defineProps({
    modelValue: { type: String, default: null },
    label: { type: String, default: "" },
    required: { type: Boolean, default: false },
    error: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const hexInput = ref(props.modelValue ?? "");

watch(() => props.modelValue, (value) => {
    hexInput.value = value ?? "";
});

const isValidHex = computed(() => /^#[0-9a-fA-F]{6}$/.test(hexInput.value));
const preview = computed(() => isValidHex.value ? hexInput.value : (props.modelValue ?? null));

function selectPreset(color) {
    hexInput.value = color;
    emit("update:modelValue", color);
}

function onHexInput(event) {
    const value = event.target.value;
    hexInput.value = value;
    if (/^#[0-9a-fA-F]{6}$/.test(value) || value === "") {
        emit("update:modelValue", value === "" ? null : value);
    }
}

function clear() {
    hexInput.value = "";
    emit("update:modelValue", null);
}
</script>

<template>
    <div class="space-y-2">
        <AppFieldLabel :label="label" :required="required" />

        <div class="grid grid-cols-8 gap-1.5">
            <button
                v-for="color in presets"
                :key="color"
                type="button"
                class="w-7 h-7 rounded-md border-2 transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-accent"
                :class="modelValue === color ? 'border-primary shadow-md scale-110' : 'border-transparent'"
                :style="{ backgroundColor: color }"
                :title="color"
                v-on:click="selectPreset(color)"
            />
        </div>

        <div class="flex items-center gap-2">
            <AppColorSwatch
                :model-value="preview ?? '#000000'"
                size="sm"
                v-on:update:model-value="selectPreset($event)"
            />
            <input
                class="flex-1 bg-surface border rounded-lg px-3 py-1.5 text-sm font-mono text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent/50 transition"
                :class="hexInput && !isValidHex ? 'border-red-400' : 'border-line'"
                :value="hexInput"
                placeholder="#3b82f6"
                maxlength="7"
                v-on:input="onHexInput"
            >
            <button
                v-if="modelValue"
                type="button"
                class="text-muted hover:text-primary transition"
                title="Effacer"
                v-on:click="clear"
            >
                <X class="w-4 h-4" :stroke-width="2" />
            </button>
        </div>

        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
