<script setup>
const props = defineProps({
    modelValue: { type: Number, required: true },
    min: { type: Number, default: 0 },
    max: { type: Number, default: 100 },
    step: { type: Number, default: 1 },
    disabled: { type: Boolean, default: false },
});

defineEmits(["update:modelValue"]);
</script>

<template>
    <input
        type="range"
        :value="modelValue"
        :min="min"
        :max="max"
        :step="step"
        :disabled="disabled"
        class="w-full accent-accent-500 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        v-on:input="$emit('update:modelValue', Number($event.target.value))"
    >
</template>
