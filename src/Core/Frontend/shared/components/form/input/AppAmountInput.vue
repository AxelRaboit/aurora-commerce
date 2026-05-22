<script setup>
/**
 * AppAmountInput — decimal amount input with optional arithmetic evaluation
 * on blur. Type "100+50" then leave the field → value becomes "150.00".
 *
 * Inspired by the Spendly UX: type math expressions (with +, -, *, /, parens)
 * to combine amounts without leaving the keyboard.
 *
 * Behavior:
 * - Allowed input chars: digits, `.`, operators `+ - * /`, parens, spaces.
 *   Other chars are stripped silently as the user types.
 * - On blur, if the value contains an operator, it is evaluated in a
 *   sandboxed Function() with the sanitized expression. On success, the
 *   result is formatted to fixed decimals (default 2) and emitted.
 * - Negative or non-finite results are rejected (raw value kept).
 * - Empty or plain numeric input is left untouched.
 *
 * Pass-through of label, error, required, placeholder to AppInput.
 */
import { ref, watch } from "vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";

const props = defineProps({
    modelValue: { type: String, default: "" },
    label: { type: String, default: "" },
    placeholder: { type: String, default: "" },
    error: { type: String, default: "" },
    required: { type: Boolean, default: false },
    decimals: { type: Number, default: 2 },
    allowNegative: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const local = ref(String(props.modelValue ?? ""));

watch(
    () => props.modelValue,
    (v) => {
        const next = String(v ?? "");
        if (next !== local.value) local.value = next;
    },
);

function sanitize(s) {
    return s.replace(/[^0-9.+\-*/()\s]/g, "");
}

function onInput(value) {
    const cleaned = sanitize(value);
    local.value = cleaned;
    emit("update:modelValue", cleaned);
}

function onBlur() {
    const cleaned = sanitize(local.value).trim();
    if (cleaned === "") return;

    // No operator → already a plain number, just normalize formatting if it's parseable.
    if (!/[+\-*/]/.test(cleaned.slice(1))) {
        const n = Number(cleaned);
        if (Number.isFinite(n)) {
            const formatted = n.toFixed(props.decimals);
            if (formatted !== local.value) {
                local.value = formatted;
                emit("update:modelValue", formatted);
            }
        }
        return;
    }

    // Has an operator → evaluate.
    try {
         
        const result = new Function(`"use strict"; return (${cleaned});`)();
        if (typeof result !== "number" || !Number.isFinite(result)) return;
        if (!props.allowNegative && result < 0) return;
        const formatted = result.toFixed(props.decimals);
        local.value = formatted;
        emit("update:modelValue", formatted);
    } catch {
        // Invalid expression — keep raw, the validator on submit will surface the error.
    }
}
</script>

<template>
    <AppInput
        :model-value="local"
        :label="label"
        :placeholder="placeholder"
        :error="error"
        :required="required"
        type="text"
        inputmode="decimal"
        v-on:update:model-value="onInput"
        v-on:blur="onBlur"
    />
</template>
