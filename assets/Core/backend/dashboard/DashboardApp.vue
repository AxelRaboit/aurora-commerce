<script setup>
import DashboardOverview from "@core/backend/dashboard/DashboardOverview.vue";

defineProps({
    stats: { type: Object, default: () => ({}) },
    enabledModules: { type: Object, default: () => ({}) },
});
</script>

<template>
    <DashboardOverview :stats="stats" :enabled-modules="enabledModules" />
</template>
