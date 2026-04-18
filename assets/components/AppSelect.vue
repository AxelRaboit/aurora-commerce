<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    options: { type: Array, default: () => [] },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" class="block text-xs text-secondary uppercase tracking-wide">{{ label }}</label>
        <select
            :value="modelValue"
            class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': error }"
            v-on:change="$emit('update:modelValue', $event.target.value)"
        >
            <slot />
        </select>
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
