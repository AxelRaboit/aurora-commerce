<script setup>
defineProps({
    href: { type: String, required: true },
    variant: { type: String, default: "admin" },
    target: { type: String, default: "_self" },
});
</script>

<template>
    <a
        :href="href"
        :target="target"
        :rel="target === '_blank' ? 'noopener' : undefined"
        :class="{
            'text-primary underline': variant === 'admin',
            'text-accent-400 hover:underline font-medium': variant === 'front',
            'text-accent hover:underline font-medium': variant === 'front-accent',
        }"
    ><slot /></a>
</template>
