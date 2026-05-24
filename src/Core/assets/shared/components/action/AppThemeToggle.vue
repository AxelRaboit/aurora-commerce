<script setup>
import { useTheme } from "@/shared/composables/useTheme";
import { Moon, Sun } from "lucide-vue-next";

const { theme, toggle } = useTheme();
</script>

<template>
    <button
        type="button"
        class="fixed top-4 right-4 z-50 p-2 rounded-lg text-muted hover:text-primary hover:bg-surface-2 border border-line transition-colors"
        v-on:click="toggle"
    >
        <Moon v-if="theme !== 'dark'" class="w-5 h-5" :stroke-width="2" />
        <Sun v-else class="w-5 h-5" :stroke-width="2" />
    </button>
</template>
