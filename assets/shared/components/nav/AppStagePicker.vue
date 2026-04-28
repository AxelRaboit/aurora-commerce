<script setup>
const props = defineProps({
    modelValue: { type: String, required: true },
    stages: { type: Array, required: true },
    labelFn: { type: Function, required: true },
    badgeFn: { type: Function, required: true },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <button
            v-for="stage in stages"
            :key="stage"
            type="button"
            :class="['px-3 py-1 rounded-md text-xs font-medium transition-all', modelValue === stage ? badgeFn(stage) + ' ring-2 ring-offset-1 ring-current ring-offset-surface' : 'bg-surface-2 text-muted hover:text-primary']"
            :disabled="disabled"
            v-on:click="emit('update:modelValue', stage)"
        >
            {{ labelFn(stage) }}
        </button>
    </div>
</template>
