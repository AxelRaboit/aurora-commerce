<script setup>
defineProps({
    href: { type: String, required: true },
    active: { type: Boolean, default: false },
    activeColor: { type: String, default: "accent" },
    target: { type: String, default: null },
    sidebarActive: { type: Boolean, default: false },
});
</script>

<template>
    <a
        :href="href"
        :target="target"
        :rel="target === '_blank' ? 'noopener' : undefined"
        :data-sidebar-active="sidebarActive ? 'true' : null"
        class="si flex items-center rounded-lg text-sm font-medium transition-colors group relative"
        :class="active
            ? (activeColor === 'rose' ? 'bg-rose-600/15 text-rose-400' : 'bg-accent-600/15 text-accent-400')
            : (activeColor === 'rose' ? 'text-secondary hover:text-rose-400 hover:bg-rose-600/10' : 'text-secondary hover:text-primary hover:bg-surface-2')"
    >
        <slot />
        <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
            <slot name="tooltip" />
        </span>
    </a>
</template>
