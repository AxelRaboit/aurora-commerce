<script setup>
/**
 * Full-width row button for dropdown menus, search result lists,
 * and any "click-a-row-to-pick" UI. Shares the visual language of
 * select option rows: padded, hover background, left-aligned content.
 *
 * Use the default slot for the main label, the `meta` slot for a
 * trailing line/text (typically muted), and `icon` slot for a leading
 * glyph.
 */
defineProps({
    active: { type: Boolean, default: false },
});
</script>

<template>
    <button
        type="button"
        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left transition-colors"
        :class="active ? 'bg-accent-600/15 text-accent-400' : 'hover:bg-surface-2 text-primary'"
    >
        <slot name="icon" />
        <span class="flex-1 min-w-0 truncate">
            <slot />
            <span v-if="$slots.meta" class="block text-xs text-muted truncate">
                <slot name="meta" />
            </span>
        </span>
    </button>
</template>
