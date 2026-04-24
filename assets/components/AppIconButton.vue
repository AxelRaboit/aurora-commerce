<script setup>
const props = defineProps({
    color: { type: String, default: "default" },
    title: { type: String, default: null },
    href: { type: String, default: null },
});

const colors = {
    default: "hover:text-primary",
    sky:     "hover:text-sky-400",
    indigo:  "hover:text-indigo-400",
    rose:    "hover:text-rose-400",
    emerald: "hover:text-emerald-400",
    amber:   "hover:text-amber-400",
};
</script>

<template>
    <component
        :is="href ? 'a' : 'button'"
        v-bind="href ? { href } : { type: 'button' }"
        :title="title"
        class="p-1.5 text-muted hover:bg-surface-2 rounded transition-colors inline-flex"
        :class="colors[color] ?? colors.default"
    >
        <slot />
    </component>
</template>
