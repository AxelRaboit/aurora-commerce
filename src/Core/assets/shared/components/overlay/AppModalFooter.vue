<script setup>
defineProps({
    bordered: { type: Boolean, default: false },
});
</script>

<template>
    <div
        class="flex flex-col sm:flex-row sm:justify-end gap-2 *:w-full sm:*:w-auto"
        :class="{ 'border-t border-line pt-2': bordered }"
    >
        <slot />
    </div>
</template>
