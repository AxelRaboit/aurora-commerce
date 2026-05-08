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
            class="w-4 h-4 rounded border border-line bg-surface text-white checked:bg-accent-600 checked:border-accent-600 focus:ring-2 focus:ring-accent-500 focus:ring-offset-0 transition-colors"
            v-on:change="$emit('update:modelValue', $event.target.checked)"
        >
        <span v-if="label || $slots.default">
            <slot>{{ label }}</slot>
        </span>
    </label>
</template>
