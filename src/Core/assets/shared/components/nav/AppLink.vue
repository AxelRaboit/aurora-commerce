<script setup>
defineProps({
    href: { type: String, required: true },
    variant: { type: String, default: "admin" },
    size: { type: String, default: "md" },
    target: { type: String, default: "_self" },
    extraClass: { type: String, default: "" },
});
</script>

<template>
    <a
        :href="href"
        :target="target"
        :rel="target === '_blank' ? 'noopener' : undefined"
        :class="[
            {
                'text-primary underline transition-colors': variant === 'admin' || variant === 'primary',
                'underline hover:opacity-80 transition-opacity': variant === 'inherit',
                'text-muted hover:text-primary transition-colors': variant === 'muted',
                'text-accent-400 hover:underline font-medium': variant === 'front',
                'text-accent hover:underline font-medium': variant === 'front-accent',
                'hover:opacity-80 transition-opacity': variant === 'front-nav',
            },
            { 'text-sm': size === 'sm' },
            extraClass,
        ]"
    ><slot /></a>
</template>
