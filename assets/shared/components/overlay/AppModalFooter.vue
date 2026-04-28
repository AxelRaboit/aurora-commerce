<script setup>
defineProps({
    bordered: { type: Boolean, default: false },
});
</script>

<template>
    <div
        class="flex flex-col sm:flex-row sm:justify-end gap-2 pt-2 [&>*]:w-full sm:[&>*]:w-auto"
        :class="{ 'border-t border-line': bordered }"
    >
        <slot />
    </div>
</template>
