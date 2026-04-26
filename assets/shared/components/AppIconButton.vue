<script setup>
const props = defineProps({
    color: { type: String, default: "default" },
    title: { type: String, default: null },
    ariaLabel: { type: String, default: null },
    href: { type: String, default: null },
});

const colors = {
    default: "hover:text-primary",
    sky:     "hover:text-sky-400",
    accent:  "hover:text-accent-400",
    rose:    "hover:text-rose-400",
    emerald: "hover:text-emerald-400",
    amber:   "hover:text-amber-400",
};

// Always project a label to assistive tech: prefer explicit ariaLabel, fall back to title.
// Components that pass neither will render an unlabelled button — caught by lint:a11y in CI.
const computedAriaLabel = props.ariaLabel ?? props.title ?? undefined;
</script>

<template>
    <component
        :is="href ? 'a' : 'button'"
        v-bind="href ? { href } : { type: 'button' }"
        :title="title"
        :aria-label="computedAriaLabel"
        class="p-1.5 text-secondary hover:bg-surface-2 rounded transition-colors inline-flex"
        :class="colors[color] ?? colors.default"
    >
        <slot />
    </component>
</template>
