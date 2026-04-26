<script setup>
defineProps({
    heading: { type: String, default: "" },
    subtitle: { type: String, default: "" },
});
</script>

<template>
    <div class="max-w-md mx-auto py-12 px-4">
        <h1 v-if="heading" class="text-2xl font-bold text-primary mb-2">{{ heading }}</h1>
        <p v-if="subtitle" class="text-secondary text-sm mb-8">{{ subtitle }}</p>

        <slot name="banner" />
        <slot />
        <slot name="footer" />
    </div>
</template>
