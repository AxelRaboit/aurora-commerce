<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    rows: { type: Number, default: 3 },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" class="block text-xs text-secondary uppercase tracking-wide">{{ label }}</label>
        <textarea
            :value="modelValue"
            :placeholder="placeholder"
            :rows="rows"
            class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': error }"
            v-on:input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
