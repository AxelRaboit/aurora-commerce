<script setup>
defineProps({
    src: { type: String, required: true },
    alt: { type: String, default: "" },
    // sm = max-h-48 | md = max-h-64 | lg = max-h-80
    size: { type: String, default: "md" },
    // full = w-full (for use inside a container that holds the border)
    full: { type: Boolean, default: false },
});

const sizes = {
    sm: "max-h-48",
    md: "max-h-64",
    lg: "max-h-80",
};
</script>

<template>
    <img
        :src="src"
        :alt="alt"
        :class="[
            'object-contain',
            sizes[size] ?? sizes.md,
            full ? 'w-full bg-surface-2' : 'rounded-lg border border-line',
        ]"
    >
</template>
