<script setup>
defineProps({
    color: { type: String, default: "gray" },
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
    <span
        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
        :class="colors[color] ?? colors.gray"
    >
        <slot />
    </span>
</template>
