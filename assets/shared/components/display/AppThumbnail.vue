<script setup>
import AppImage from "./AppImage.vue";

defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: "" },
    // sm = 10×10 | md = 12×12 | landscape = 16×10
    size: { type: String, default: "sm" },
});

const sizes = {
    sm:        "w-10 h-10",
    md:        "w-12 h-12",
    landscape: "w-16 h-10",
};
</script>

<template>
    <div
        class="shrink-0 rounded border border-line bg-surface-2 overflow-hidden"
        :class="sizes[size] ?? sizes.sm"
    >
        <AppImage
            v-if="src"
            :src="src"
            :alt="alt"
            object-fit="cover"
            :fallback-icon="false"
        />
        <slot v-else />
    </div>
</template>
