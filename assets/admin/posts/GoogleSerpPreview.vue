<script setup>
import { computed } from "vue";

const props = defineProps({
    title: { type: String, default: "" },
    description: { type: String, default: "" },
    slug: { type: String, default: "" },
    siteUrl: { type: String, default: window.location.origin },
});

const displayUrl = computed(() => {
    try {
        const url = new URL(props.slug || "/", props.siteUrl);
        return url.hostname + (url.pathname === "/" ? "" : url.pathname);
    } catch {
        return props.siteUrl;
    }
});

const truncatedTitle = computed(() => {
    if (!props.title) return "";
    return props.title.length > 60 ? props.title.slice(0, 57) + "…" : props.title;
});

const truncatedDesc = computed(() => {
    if (!props.description) return "";
    return props.description.length > 160 ? props.description.slice(0, 157) + "…" : props.description;
});
</script>

<template>
    <div class="max-w-[600px] p-4 border border-line rounded-lg bg-white dark:bg-zinc-950 space-y-1" style="font-family: Arial, sans-serif">
        <div class="text-xs text-[#202124] dark:text-zinc-400 truncate">{{ displayUrl }}</div>
        <div class="text-[20px] leading-snug text-[#1a0dab] dark:text-[#8ab4f8] truncate">
            {{ truncatedTitle || "&nbsp;" }}
        </div>
        <div class="text-sm text-[#4d5156] dark:text-zinc-400 leading-relaxed" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden">
            {{ truncatedDesc }}
        </div>
    </div>
</template>
