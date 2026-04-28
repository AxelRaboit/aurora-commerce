<script setup>
defineProps({
    label: { type: String, default: '' },
    required: { type: Boolean, default: false },
});
</script>

<template>
    <label v-if="label" class="block text-xs text-secondary uppercase tracking-wide">
        {{ label }}<span v-if="required" class="text-red-500 ml-0.5">*</span>
    </label>
</template>
