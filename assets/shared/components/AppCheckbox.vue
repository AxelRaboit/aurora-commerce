<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: '' },
    name: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="flex items-center gap-2 text-sm text-secondary cursor-pointer select-none" :class="{ 'opacity-50 cursor-not-allowed': disabled }">
        <input
            type="checkbox"
            :name="name || undefined"
            :checked="modelValue"
            :disabled="disabled"
            class="w-4 h-4 rounded border-line bg-surface-2 text-accent-600 focus:ring-accent-500 focus:ring-offset-0"
            v-on:change="$emit('update:modelValue', $event.target.checked)"
        >
        <span v-if="label || $slots.default">
            <slot>{{ label }}</slot>
        </span>
    </label>
</template>
