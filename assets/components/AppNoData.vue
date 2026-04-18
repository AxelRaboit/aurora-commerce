<script setup>
import { Inbox } from "lucide-vue-next";

defineProps({
    message: { type: String, default: "Aucune donnée à afficher." },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 py-10 text-muted">
        <Inbox class="w-8 h-8 opacity-40" :stroke-width="1.5" />
        <p class="text-sm">{{ message }}</p>
    </div>
</template>
