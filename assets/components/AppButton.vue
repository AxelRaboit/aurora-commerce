<script setup>
import { Loader2 } from "lucide-vue-next";

defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    href: { type: String, default: null },
});

const base = 'inline-flex items-center justify-center gap-2 rounded transition duration-150 ease-in-out focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed';

const variants = {
    primary: 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-indigo-500 font-bold',
    secondary: 'bg-surface-3 hover:bg-surface-2 text-primary border border-line focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-base font-bold',
    danger: 'bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-600/40 font-medium',
    'danger-outline': 'bg-transparent hover:bg-rose-500/10 text-rose-400 border border-line focus:ring-2 focus:ring-offset-2 focus:ring-offset-surface focus:ring-rose-500 font-bold',
    ghost: 'bg-transparent hover:bg-surface-2 text-secondary hover:text-primary',
    link: 'bg-transparent text-muted hover:text-secondary underline p-0',
    icon: 'bg-transparent p-0',
};

const sizes = {
    sm: 'py-1.5 px-3 text-xs',
    md: 'py-2 px-4',
    lg: 'py-3 px-6 text-base',
    none: '',
};
</script>

<template>
    <a
        v-if="href"
        :href="href"
        :class="[base, variants[variant] ?? variants.primary, sizes[size] ?? sizes.md]"
    >
        <slot />
    </a>
    <button
        v-else
        :type="type"
        :disabled="disabled || loading"
        :class="[base, variants[variant] ?? variants.primary, sizes[size] ?? sizes.md]"
    >
        <Loader2 v-if="loading" class="animate-spin h-4 w-4" :stroke-width="2" />
        <slot />
    </button>
</template>
