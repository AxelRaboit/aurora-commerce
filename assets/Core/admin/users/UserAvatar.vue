<script setup>
import { computed } from "vue";

const props = defineProps({
    name: { type: String, default: "" },
    photoUrl: { type: String, default: "" },
    size: { type: Number, default: 32 },
});

const initial = computed(() => props.name?.charAt(0)?.toUpperCase() || "?");
const dimension = computed(() => `${props.size}px`);
const fontSize = computed(() => `${Math.max(10, Math.round(props.size * 0.42))}px`);
</script>

<template>
    <div
        class="rounded-full overflow-hidden bg-accent-600 text-white flex items-center justify-center font-semibold shrink-0"
        :style="{ width: dimension, height: dimension, fontSize }"
    >
        <img v-if="photoUrl" :src="photoUrl" :alt="name" class="w-full h-full object-cover">
        <span v-else>{{ initial }}</span>
    </div>
</template>
