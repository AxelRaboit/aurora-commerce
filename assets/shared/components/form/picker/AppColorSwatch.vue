<script setup>
defineProps({
    modelValue: { type: String, default: "" },
    size: { type: String, default: "md" },
    disabled: { type: Boolean, default: false },
});

defineEmits(["update:modelValue"]);

const sizes = {
    sm: "w-8 h-8 p-0.5",
    md: "w-10 h-10",
};
</script>

<template>
    <input
        type="color"
        :value="modelValue"
        :disabled="disabled"
        class="rounded cursor-pointer border border-line bg-transparent"
        :class="sizes[size] ?? sizes.md"
        v-on:input="$emit('update:modelValue', $event.target.value)"
    >
</template>
