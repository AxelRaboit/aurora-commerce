<script setup>
/**
 * 2-column responsive toolbar for admin list pages. Mobile = stacked,
 * desktop (sm+) = search left (1fr) + actions right (auto).
 *
 * Default slot = left content (typically AppSearchInput).
 * `actions` slot = right content (typically one or more AppButton).
 *
 * The component is layout-only; consumers compose the search input and
 * action buttons themselves so it stays usable for any admin list page.
 */
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
        <slot />
        <slot name="actions" />
    </div>
</template>
