<script setup>
defineProps({
    title: { type: String, default: null },
    crumbs: { type: Array, default: null },
});
</script>

<template>
    <nav v-if="crumbs" class="flex items-center gap-2 text-sm">
        <template v-for="(crumb, i) in crumbs" :key="i">
            <span v-if="i > 0" class="text-subtle select-none">/</span>
            <a
                v-if="crumb.href"
                :href="crumb.href"
                class="text-secondary hover:text-primary transition-colors"
            >
                {{ crumb.label }}
            </a>
            <span v-else class="text-primary font-medium">{{ crumb.label }}</span>
        </template>
    </nav>

    <h2 v-else class="text-xl font-semibold leading-tight text-primary">{{ title }}</h2>
</template>
