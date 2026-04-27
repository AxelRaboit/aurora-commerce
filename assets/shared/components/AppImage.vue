<script setup>
import { ref } from "vue";
import { Image as ImageIcon } from "lucide-vue-next";

const props = defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: "" },
    objectFit: { type: String, default: "cover" }, // cover | contain | fill | none
    focalPoint: { type: String, default: "50% 50%" },
    loading: { type: String, default: "lazy" }, // lazy | eager
    rounded: { type: String, default: "" }, // any Tailwind rounded class
    fallbackIcon: { type: Boolean, default: true },
});

const status = ref("idle"); // idle | loading | loaded | error

function onLoad() { status.value = "loaded"; }
function onError() { status.value = "error"; }
function onLoadStart() { status.value = "loading"; }
</script>

<template>
    <div class="relative overflow-hidden bg-surface-2 w-full h-full" :class="rounded">
        <!-- Skeleton while loading -->
        <div
            v-if="status === 'loading'"
            class="absolute inset-0 animate-pulse bg-surface-3"
        />

        <!-- Image -->
        <img
            v-if="src && status !== 'error'"
            :src="src"
            :alt="alt"
            :loading="loading"
            class="w-full h-full transition-opacity duration-300"
            :class="[
                `object-${objectFit}`,
                status === 'loaded' ? 'opacity-100' : 'opacity-0',
            ]"
            :style="{ objectPosition: focalPoint }"
            v-on:loadstart="onLoadStart"
            v-on:load="onLoad"
            v-on:error="onError"
        >

        <!-- Fallback on error or no src -->
        <div
            v-if="(status === 'error' || !src) && fallbackIcon"
            class="absolute inset-0 flex items-center justify-center"
        >
            <ImageIcon class="w-1/3 max-w-10 text-muted/40" :stroke-width="1.5" />
        </div>
    </div>
</template>
