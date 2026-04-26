<script setup>
import { getCurrentInstance } from "vue";

defineProps({
    size: { type: Number, default: 40 },
});

const instanceUid = getCurrentInstance().uid;
const gradientId = `velox-bg-${instanceUid}`;
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient
                :id="gradientId"
                x1="0%"
                y1="0%"
                x2="100%"
                y2="100%"
            >
                <stop offset="0%" style="stop-color:#6366f1" />
                <stop offset="100%" style="stop-color:#4f46e5" />
            </linearGradient>
        </defs>
        <rect width="64" height="64" rx="14" :fill="`url(#${gradientId})`" />
        <text
            x="32"
            y="45"
            font-family="'Inter', 'Segoe UI', sans-serif"
            font-size="36"
            font-weight="700"
            text-anchor="middle"
            fill="white"
        >V</text>
        <line
            x1="20"
            y1="52"
            x2="44"
            y2="52"
            stroke="rgba(255,255,255,0.4)"
            stroke-width="2.5"
            stroke-linecap="round"
        />
    </svg>
</template>
