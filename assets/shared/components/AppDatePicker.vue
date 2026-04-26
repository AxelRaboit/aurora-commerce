<script setup>
import { VueDatePicker } from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import AppFieldLabel from "@/shared/components/AppFieldLabel.vue";
import { useTheme } from "@/shared/composables/useTheme.js";
import { useI18n } from "vue-i18n";
import { computed } from "vue";
import { fr, enUS, es, de } from "date-fns/locale";

const { theme } = useTheme();
const { locale } = useI18n();
const isDark = computed(() => theme.value === "dark");

const LOCALES = { fr, en: enUS, es, de };
const dateFnsLocale = computed(() => LOCALES[locale.value] ?? enUS);

const props = defineProps({
    modelValue: { type: String, default: "" },
    label: { type: String, default: "" },
    placeholder: { type: String, default: "" },
    required: { type: Boolean, default: false },
    error: { type: String, default: "" },
    enableTime: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

function onUpdate(val) {
    if (!val) { emit("update:modelValue", ""); return; }
    const d = new Date(val);
    const pad = (n) => String(n).padStart(2, "0");
    if (props.enableTime) {
        emit("update:modelValue", `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`);
    } else {
        emit("update:modelValue", `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`);
    }
}

const internalValue = computed(() => {
    if (!props.modelValue) return null;
    const d = new Date(props.modelValue);
    return isNaN(d.getTime()) ? null : d;
});
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <AppFieldLabel :label="label" :required="required" />
        <VueDatePicker
            :model-value="internalValue"
            :dark="isDark"
            :locale="dateFnsLocale"
            :enable-time-picker="enableTime"
            :placeholder="placeholder"
            auto-apply
            :teleport="true"
            input-class-name="dp-custom-input"
            v-on:update:model-value="onUpdate"
        />
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<style>
.dp-custom-input {
    width: 100%;
    border-radius: 0.375rem;
    border: 1px solid var(--color-line);
    background: var(--color-surface);
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: var(--color-primary);
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
}
.dp-custom-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 1px #6366f1;
}
</style>
