<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { Search, FileText, Tags as TagsIcon, Image, Loader2, X } from "lucide-vue-next";
import { statusBadge } from "@/utils/statusStyles.js";

const { t } = useI18n();

const props = defineProps({
    searchPath: { type: String, default: "/admin/search" },
    postsPath: { type: String, default: "/admin/posts" },
    taxonomiesPath: { type: String, default: "/admin/taxonomies" },
    mediaPath: { type: String, default: "/admin/media" },
});

const open = ref(false);
const query = ref("");
const results = ref({ posts: [], terms: [], media: [] });
const loading = ref(false);
const highlightedIndex = ref(0);
const inputRef = ref(null);
let debounceTimer = null;

const isMac = typeof navigator !== "undefined" && /Mac|iP(hone|od|ad)/.test(navigator.platform);
const modKeyLabel = isMac ? "⌘" : "Ctrl";

const flatResults = computed(() => [
    ...results.value.posts.map((item) => ({ kind: "post", item })),
    ...results.value.terms.map((item) => ({ kind: "term", item })),
    ...results.value.media.map((item) => ({ kind: "media", item })),
]);

const totalResults = computed(() => flatResults.value.length);

function openPalette() {
    open.value = true;
    query.value = "";
    results.value = { posts: [], terms: [], media: [] };
    highlightedIndex.value = 0;
    nextTick(() => inputRef.value?.focus());
}

function closePalette() {
    open.value = false;
}

function onGlobalKeydown(event) {
    if ((event.ctrlKey || event.metaKey) && "k" === event.key.toLowerCase()) {
        event.preventDefault();
        open.value ? closePalette() : openPalette();
        return;
    }
    if (!open.value) return;
    if ("Escape" === event.key) {
        event.preventDefault();
        closePalette();
    } else if ("ArrowDown" === event.key) {
        event.preventDefault();
        if (totalResults.value) highlightedIndex.value = (highlightedIndex.value + 1) % totalResults.value;
    } else if ("ArrowUp" === event.key) {
        event.preventDefault();
        if (totalResults.value) highlightedIndex.value = (highlightedIndex.value - 1 + totalResults.value) % totalResults.value;
    } else if ("Enter" === event.key) {
        event.preventDefault();
        activateResult(flatResults.value[highlightedIndex.value]);
    }
}

watch(query, () => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(runSearch, 180);
});

async function runSearch() {
    const trimmed = query.value.trim();
    if ("" === trimmed) {
        results.value = { posts: [], terms: [], media: [] };
        return;
    }
    loading.value = true;
    try {
        const url = new URL(props.searchPath, window.location.origin);
        url.searchParams.set("q", trimmed);
        const response = await fetch(url);
        if (!response.ok) throw new Error();
        const data = await response.json();
        results.value = {
            posts: data.posts ?? [],
            terms: data.terms ?? [],
            media: data.media ?? [],
        };
        highlightedIndex.value = 0;
    } catch {
        results.value = { posts: [], terms: [], media: [] };
    } finally {
        loading.value = false;
    }
}

function activateResult(entry) {
    if (!entry) return;
    if ("post" === entry.kind) {
        const url = new URL(props.postsPath, window.location.origin);
        if (entry.item.trashed) url.searchParams.set("trashed", "1");
        window.location.href = url.toString();
    } else if ("term" === entry.kind) {
        window.location.href = props.taxonomiesPath;
    } else if ("media" === entry.kind) {
        window.location.href = props.mediaPath;
    }
}

function highlightMatch(text) {
    if (!text || !query.value) return text ?? "";
    const tokens = query.value
        .trim()
        .split(/\s+/)
        .filter((token) => token.length > 1)
        .map((token) => token.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));
    if (!tokens.length) return text;
    const regex = new RegExp(`(${tokens.join("|")})`, "ig");
    return text.replace(regex, '<mark class="bg-indigo-400/30 text-primary rounded px-0.5">$1</mark>');
}

function entryIndex(kind, item) {
    return flatResults.value.findIndex((entry) => entry.kind === kind && entry.item.id === item.id);
}

onMounted(() => window.addEventListener("keydown", onGlobalKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", onGlobalKeydown));
</script>

<template>
    <Teleport to="body">
        <button
            type="button"
            class="fixed bottom-4 right-4 z-40 inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-surface border border-line text-sm text-secondary hover:text-primary hover:bg-surface-2 shadow-sm lg:hidden"
            v-on:click="openPalette"
        >
            <Search class="w-4 h-4" :stroke-width="2" />
            {{ t("search.button") }}
        </button>

        <button
            type="button"
            class="hidden lg:inline-flex fixed bottom-4 right-4 z-40 items-center gap-2 px-3 py-2 rounded-lg bg-surface border border-line text-sm text-secondary hover:text-primary hover:bg-surface-2 shadow-sm"
            v-on:click="openPalette"
        >
            <Search class="w-4 h-4" :stroke-width="2" />
            {{ t("search.button") }}
            <kbd class="ml-1 px-1.5 py-0.5 rounded bg-surface-2 border border-line font-mono text-[10px] text-muted">{{ modKeyLabel }}+K</kbd>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4" v-on:click.self="closePalette">
                <div class="fixed inset-0 bg-black/60" v-on:click="closePalette" />

                <div class="relative w-full max-w-2xl bg-surface border border-line rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[70vh]">
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-line">
                        <Search class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
                        <input
                            ref="inputRef"
                            v-model="query"
                            type="text"
                            :placeholder="t('search.placeholder')"
                            class="flex-1 bg-transparent border-0 outline-none text-primary placeholder-muted text-sm"
                        >
                        <Loader2 v-if="loading" class="w-4 h-4 text-muted animate-spin" :stroke-width="2" />
                        <button type="button" class="p-1 text-muted hover:text-primary rounded" v-on:click="closePalette">
                            <X class="w-4 h-4" :stroke-width="2" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <div v-if="!query.trim()" class="px-4 py-8 text-sm text-muted text-center">
                            {{ t("search.hint") }}
                        </div>
                        <div v-else-if="!loading && totalResults === 0" class="px-4 py-8 text-sm text-muted text-center">
                            {{ t("search.empty") }}
                        </div>

                        <!-- Posts -->
                        <div v-if="results.posts.length" class="px-2 py-2 space-y-1">
                            <p class="px-2 py-1 text-[11px] uppercase tracking-wide text-muted font-semibold flex items-center gap-1.5">
                                <FileText class="w-3 h-3" :stroke-width="2" />
                                {{ t("search.sections.posts") }}
                            </p>
                            <button
                                v-for="post in results.posts"
                                :key="`post-${post.id}`"
                                type="button"
                                class="w-full text-left px-2 py-2 rounded-md transition-colors flex items-start gap-3"
                                :class="entryIndex('post', post) === highlightedIndex ? 'bg-indigo-600/15 text-indigo-400' : 'hover:bg-surface-2'"
                                v-on:mouseenter="highlightedIndex = entryIndex('post', post)"
                                v-on:click="activateResult({ kind: 'post', item: post })"
                            >
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium shrink-0 mt-0.5" :class="statusBadge(post.status)">
                                    {{ t("admin.stats.postStatus." + post.status) }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-primary truncate" v-html="highlightMatch(post.title ?? '(—)')" />
                                    <div v-if="post.snippet" class="text-xs text-muted line-clamp-2" v-html="highlightMatch(post.snippet)" />
                                    <div class="text-[11px] text-muted mt-0.5">{{ post.postType }}</div>
                                </div>
                            </button>
                        </div>

                        <!-- Terms -->
                        <div v-if="results.terms.length" class="px-2 py-2 space-y-1 border-t border-line">
                            <p class="px-2 py-1 text-[11px] uppercase tracking-wide text-muted font-semibold flex items-center gap-1.5">
                                <TagsIcon class="w-3 h-3" :stroke-width="2" />
                                {{ t("search.sections.terms") }}
                            </p>
                            <button
                                v-for="term in results.terms"
                                :key="`term-${term.id}`"
                                type="button"
                                class="w-full text-left px-2 py-2 rounded-md transition-colors flex items-center gap-3"
                                :class="entryIndex('term', term) === highlightedIndex ? 'bg-indigo-600/15 text-indigo-400' : 'hover:bg-surface-2'"
                                v-on:mouseenter="highlightedIndex = entryIndex('term', term)"
                                v-on:click="activateResult({ kind: 'term', item: term })"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-primary truncate" v-html="highlightMatch(term.name ?? '(—)')" />
                                    <div class="text-[11px] text-muted">{{ term.taxonomy }}</div>
                                </div>
                            </button>
                        </div>

                        <!-- Media -->
                        <div v-if="results.media.length" class="px-2 py-2 space-y-1 border-t border-line">
                            <p class="px-2 py-1 text-[11px] uppercase tracking-wide text-muted font-semibold flex items-center gap-1.5">
                                <Image class="w-3 h-3" :stroke-width="2" />
                                {{ t("search.sections.media") }}
                            </p>
                            <button
                                v-for="media in results.media"
                                :key="`media-${media.id}`"
                                type="button"
                                class="w-full text-left px-2 py-2 rounded-md transition-colors flex items-center gap-3"
                                :class="entryIndex('media', media) === highlightedIndex ? 'bg-indigo-600/15 text-indigo-400' : 'hover:bg-surface-2'"
                                v-on:mouseenter="highlightedIndex = entryIndex('media', media)"
                                v-on:click="activateResult({ kind: 'media', item: media })"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-primary truncate" v-html="highlightMatch(media.name ?? '(—)')" />
                                    <div class="text-[11px] text-muted">{{ media.mimeType }}</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="px-4 py-2 border-t border-line bg-surface-2/50 text-[11px] text-muted flex items-center gap-4">
                        <span><kbd class="px-1 py-0.5 rounded bg-surface border border-line font-mono text-[10px]">↑↓</kbd> {{ t("search.keys.navigate") }}</span>
                        <span><kbd class="px-1 py-0.5 rounded bg-surface border border-line font-mono text-[10px]">Enter</kbd> {{ t("search.keys.select") }}</span>
                        <span class="ml-auto"><kbd class="px-1 py-0.5 rounded bg-surface border border-line font-mono text-[10px]">Esc</kbd> {{ t("search.keys.close") }}</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
