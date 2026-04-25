<script setup>
import { useI18n } from "vue-i18n";

const { t } = useI18n();

defineProps({
    comment: { type: Object, required: true },
    reactionEmojis: { type: Object, default: () => ({}) },
});

defineEmits(["react"]);

const btnClass = "inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-surface-2 hover:bg-surface-3 border border-line transition-colors";
</script>

<template>
    <div class="flex items-center gap-1 mt-3 flex-wrap">
        <template v-for="(emoji, type) in reactionEmojis" :key="type">
            <button
                v-if="(comment.reactionCounts?.[type] ?? 0) > 0"
                type="button"
                :class="btnClass"
                v-on:click="$emit('react', comment.id, type)"
            >
                {{ emoji }} {{ comment.reactionCounts[type] }}
            </button>
        </template>

        <div class="relative group">
            <button type="button" :class="btnClass + ' text-muted hover:text-primary'">
                ＋ {{ t("comment.react") }}
            </button>
            <div class="hidden group-hover:flex absolute left-0 top-full mt-1 z-10 gap-1 p-2 bg-surface border border-line rounded-xl shadow-lg">
                <button
                    v-for="(emoji, type) in reactionEmojis"
                    :key="type"
                    type="button"
                    class="text-lg hover:scale-125 transition-transform p-1"
                    v-on:click="$emit('react', comment.id, type)"
                >
                    {{ emoji }}
                </button>
            </div>
        </div>
    </div>
</template>
