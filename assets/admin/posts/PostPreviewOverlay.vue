<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { X } from "lucide-vue-next";
import { renderBlocks } from "@/utils/blocksRenderer.js";

const { t } = useI18n();

const props = defineProps({
    post:    { type: Object,  default: null },
    loading: { type: Boolean, default: false },
    locales: { type: Array,   default: () => [] },
});

const emit = defineEmits(["close"]);

const activeLocale = ref(props.locales[0] ?? "fr");

watch(() => props.post, (newPost) => {
    if (newPost) activeLocale.value = props.locales[0] ?? "fr";
});

const previewHtml = computed(() =>
    renderBlocks(props.post?.translations?.[activeLocale.value]?.blocks ?? []),
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="post || loading" class="fixed inset-0 z-50 flex flex-col bg-bg overflow-y-auto">
                <!-- Header -->
                <div class="sticky top-0 z-10 flex items-center gap-3 px-6 py-3 border-b border-line bg-surface/90 backdrop-blur-sm shrink-0">
                    <span class="flex-1 text-sm font-medium text-secondary truncate">
                        {{ post?.title ?? "…" }}
                    </span>
                    <!-- Locale switcher -->
                    <div v-if="post" class="flex gap-1">
                        <button
                            v-for="locale in locales"
                            :key="locale"
                            type="button"
                            class="px-2.5 py-1 text-xs font-medium rounded transition-colors"
                            :class="activeLocale === locale
                                ? 'bg-indigo-600 text-white'
                                : 'text-secondary hover:bg-surface-2'"
                            v-on:click="activeLocale = locale"
                        >
                            {{ t("locales." + locale) }}
                        </button>
                    </div>
                    <button
                        type="button"
                        class="p-1.5 rounded-lg text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                        v-on:click="$emit('close')"
                    >
                        <X class="w-5 h-5" :stroke-width="2" />
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 w-full max-w-3xl mx-auto px-6 py-12">
                    <div v-if="loading" class="text-secondary text-sm">{{ t("common.loading") }}</div>
                    <template v-else-if="post">
                        <h1 v-if="post.translations?.[activeLocale]?.title" class="text-3xl font-bold text-primary mb-8">
                            {{ post.translations[activeLocale].title }}
                        </h1>
                        <div v-if="previewHtml" class="prose-preview" v-html="previewHtml" />
                        <p v-else class="text-muted text-sm italic">{{ t("admin.posts.previewEmpty") }}</p>
                    </template>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
