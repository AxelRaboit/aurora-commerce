<script setup>
import AppFieldLabel from "@/shared/components/AppFieldLabel.vue";

defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    rows: { type: Number, default: 3 },
    mono: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <AppFieldLabel :label="label" :required="required" />
        <textarea
            :value="modelValue"
            :placeholder="placeholder"
            :rows="rows"
            class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"
            :class="[
                { 'border-red-500 focus:border-red-500 focus:ring-red-500': error },
                mono ? 'text-xs font-mono' : 'text-sm',
            ]"
            v-on:input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
