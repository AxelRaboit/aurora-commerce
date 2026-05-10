<script setup>
import { useI18n } from "vue-i18n";
import { buildTermTree, flattenTreeWithDepth } from "@editorial/shared/termTree.js";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";

const { t } = useI18n();

const props = defineProps({
    taxonomies: { type: Array, required: true },
    selectedTermIds: { type: Array, required: true },
    activeLocale: { type: String, required: true },
    defaultLocale: { type: String, required: true },
});

const emit = defineEmits(["toggle-term"]);

function taxonomyLabel(taxonomy) {
    return (
        taxonomy.translations?.[props.activeLocale]?.label ??
        taxonomy.translations?.[props.defaultLocale]?.label ??
        taxonomy.slug
    );
}

function termLabel(term) {
    return (
        term.translations?.[props.activeLocale]?.name ??
        term.translations?.[props.defaultLocale]?.name ??
        "(—)"
    );
}
</script>

<template>
    <div v-for="taxonomy in taxonomies" :key="taxonomy.id" class="space-y-2">
        <div class="flex items-center gap-2">
            <span class="text-xs text-muted uppercase tracking-wide shrink-0">{{ taxonomyLabel(taxonomy) }}</span>
            <span v-if="taxonomy.hierarchical" class="text-xs px-1.5 py-0.5 rounded bg-sky-500/15 text-sky-400">
                {{ t("backend.taxonomies.hierarchical") }}
            </span>
        </div>

        <div v-if="!taxonomy.hierarchical" class="flex items-center gap-2 flex-wrap">
            <label
                v-for="term in taxonomy.terms"
                :key="term.id"
                class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium cursor-pointer border transition-colors select-none"
                :class="selectedTermIds.includes(term.id)
                    ? 'bg-accent-600 border-accent-600 text-white'
                    : 'bg-surface-2 border-line text-secondary hover:border-accent-400 hover:text-primary'"
            >
                <input type="checkbox" class="sr-only" :checked="selectedTermIds.includes(term.id)" v-on:change="emit('toggle-term', term.id)">
                {{ termLabel(term) }}
            </label>
            <AppNoData v-if="!taxonomy.terms.length" :message="t('backend.posts.termsPickerEmpty')" />
        </div>

        <div v-else class="max-h-60 overflow-y-auto scrollbar-thin border border-line/60 rounded-md bg-surface-2 p-2 space-y-1">
            <AppNoData v-if="!taxonomy.terms.length" :message="t('backend.posts.termsPickerEmpty')" />
            <label
                v-for="term in flattenTreeWithDepth(buildTermTree(taxonomy.terms))"
                :key="term.id"
                class="flex items-center gap-2 text-sm cursor-pointer hover:bg-surface-3 rounded px-1.5 py-0.5"
                :style="{ paddingLeft: `${0.375 + term.depth * 1.25}rem` }"
            >
                <input
                    type="checkbox"
                    class="w-4 h-4 rounded border-line bg-surface text-accent-600 focus:ring-accent-500 focus:ring-offset-0"
                    :checked="selectedTermIds.includes(term.id)"
                    v-on:change="emit('toggle-term', term.id)"
                >
                <span class="text-primary">{{ termLabel(term) }}</span>
            </label>
        </div>
    </div>
</template>
