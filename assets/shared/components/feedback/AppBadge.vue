<script setup>
/**
 * Coloured pill. Renders as a `<span>` by default, or as an `<a>` when an
 * `href` is provided — handy for status filters / counts that link to a
 * filtered list. The hover state is only added in linked mode.
 */
defineProps({
    color: { type: String, default: "gray" },
    href: { type: String, default: null },
});

const colors = {
    accent: "bg-accent-600/15 text-accent-400",
    rose: "bg-rose-500/15 text-rose-400",
    sky: "bg-sky-500/15 text-sky-400",
    amber: "bg-amber-500/15 text-amber-400",
    emerald: "bg-emerald-500/15 text-emerald-400",
    violet: "bg-violet-500/15 text-violet-400",
    slate: "bg-slate-500/15 text-slate-400",
    gray: "bg-surface-2 text-secondary",
};
</script>

<template>
    <component
        :is="href ? 'a' : 'span'"
        v-bind="href ? { href } : {}"
        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
        :class="[
            colors[color] ?? colors.gray,
            href ? 'hover:opacity-80 transition-opacity cursor-pointer' : '',
        ]"
    >
        <slot />
    </component>
</template>
