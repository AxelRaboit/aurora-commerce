<script setup>
defineProps({
    color: { type: String, default: "default" },
    title: { type: String, default: null },
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
    <button
        type="button"
        :title="title"
        class="p-1.5 text-muted hover:bg-surface-2 rounded transition-colors"
        :class="colors[color] ?? colors.default"
    >
        <slot />
    </button>
</template>
