<script setup>
import { ref, reactive, computed, onMounted, watch, provide } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { ArrowLeft, Save, Eye, X, LayoutTemplate } from "lucide-vue-next";
import { renderBlocks } from "@/utils/blocksRenderer.js";
import AppInput from "@/components/AppInput.vue";
import AppTextarea from "@/components/AppTextarea.vue";
import AppSelect from "@/components/AppSelect.vue";
import EditorBlock from "@/components/EditorBlock.vue";
import { usePostSave } from "./composables/usePostSave.js";
import { TEMPLATES } from "@/utils/editorjs/templates.js";
import { slugify } from "@/utils/slugify.js";
import { DEFAULT_LOCALES } from "@/utils/lang.js";

const { t } = useI18n();

const props = defineProps({
    postId: { type: Number, default: null },
    postTypes: { type: Array, default: () => [] },
    allTags: { type: Array, default: () => [] },
    locales: { type: Array, default: () => DEFAULT_LOCALES },
    showPath: { type: String, required: true },
    createPath: { type: String, required: true },
    editPath: { type: String, required: true },
});

const emit = defineEmits(["saved", "back"]);

const activeLocale = ref(props.locales[0] ?? "fr");
const fetching = ref(false);

let flushEditor = null;
provide("registerEditorFlush", (fn) => { flushEditor = fn; });

function makeEmptyTranslation() {
    return { title: "", slug: "", blocks: [], metaTitle: "", metaDescription: "", customFields: {} };
}

const form = reactive({
    postTypeId: String(props.postTypes[0]?.id ?? ""),
    status: "draft",
    featuredMediaId: null,
    tagIds: [],
    translations: Object.fromEntries(props.locales.map((locale) => [locale, makeEmptyTranslation()])),
});

watch(
    () => form.translations[activeLocale.value]?.title,
    (newTitle) => {
        const translation = form.translations[activeLocale.value];
        if (translation) translation.slug = slugify(newTitle);
    },
);

async function switchLocale(locale) {
    if (locale === activeLocale.value) return;
    await flushEditor?.();
    activeLocale.value = locale;
}

onMounted(async () => {
    if (!props.postId) return;
    fetching.value = true;
    try {
        const response = await fetch(props.showPath.replace("__id__", props.postId));
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success) {
            form.postTypeId = String(data.post.postType.id);
            form.status = data.post.status;
            form.featuredMediaId = data.post.featuredMediaId ?? null;
            form.tagIds = [...(data.post.tagIds ?? [])];
            for (const [locale, translation] of Object.entries(data.post.translations ?? {})) {
                if (form.translations[locale]) {
                    Object.assign(form.translations[locale], translation);
                }
            }
        }
    } catch {
        toast.error(t("common.error"));
        emit("back");
    } finally {
        fetching.value = false;
    }
});

function toggleTag(tagId) {
    const index = form.tagIds.indexOf(tagId);
    if (index === -1) {
        form.tagIds.push(tagId);
    } else {
        form.tagIds.splice(index, 1);
    }
}

const showPreview = ref(false);
const showTemplates = ref(false);
const editorKey = ref(0);

function applyTemplate(template) {
    form.translations[activeLocale.value].blocks = structuredClone(template.blocks);
    showTemplates.value = false;
    editorKey.value++;
}

const previewHtml = computed(() =>
    renderBlocks(form.translations[activeLocale.value]?.blocks ?? []),
);

const { loading, errors, save: savePost } = usePostSave(
    props.createPath,
    props.editPath,
    (post) => {
        toast.success(props.postId ? t("admin.posts.updated") : t("admin.posts.created"));
        emit("saved", post, !props.postId);
    },
);

async function handleSave() {
    await flushEditor?.();
    await savePost(props.postId, {
        postTypeId: Number(form.postTypeId),
        status: form.status,
        featuredMediaId: form.featuredMediaId,
        tagIds: form.tagIds,
        translations: form.translations,
    });
}
</script>

<template>
    <div v-if="fetching" class="flex items-center justify-center py-20 text-secondary text-sm">
        {{ t("common.loading") }}
    </div>

    <div v-else class="space-y-6">
        <!-- Top bar -->
        <div class="flex items-center gap-3 flex-wrap">
            <button
                type="button"
                class="p-2 rounded-lg text-secondary hover:text-primary hover:bg-surface-2 transition-colors shrink-0"
                v-on:click="$emit('back')"
            >
                <ArrowLeft class="w-5 h-5" :stroke-width="2" />
            </button>
            <h1 class="flex-1 text-lg font-semibold text-primary truncate min-w-0">
                {{ postId ? t("admin.posts.edit") : t("admin.posts.add") }}
            </h1>
            <AppSelect v-model="form.status">
                <option value="draft">{{ t("admin.posts.statusOptions.draft") }}</option>
                <option value="published">{{ t("admin.posts.statusOptions.published") }}</option>
                <option value="trash">{{ t("admin.posts.statusOptions.trash") }}</option>
            </AppSelect>
            <button
                type="button"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-surface-2 border border-line hover:bg-surface-3 text-secondary transition-colors shrink-0"
                v-on:click="showTemplates = true"
            >
                <LayoutTemplate class="w-4 h-4" :stroke-width="2" />
                Templates
            </button>
            <button
                type="button"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-surface-2 border border-line hover:bg-surface-3 text-secondary transition-colors shrink-0"
                v-on:click="showPreview = true"
            >
                <Eye class="w-4 h-4" :stroke-width="2" />
                {{ t("admin.posts.preview") }}
            </button>
            <button
                type="button"
                :disabled="loading"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50 transition-colors shrink-0"
                v-on:click="handleSave"
            >
                <Save class="w-4 h-4" :stroke-width="2" />
                {{ loading ? t("common.loading") : t("common.save") }}
            </button>
        </div>

        <!-- Global save errors -->
        <div v-if="Object.keys(errors).length" class="rounded-lg border border-red-500/40 bg-red-500/10 p-3 text-sm text-red-400 space-y-1">
            <p v-for="(message, field) in errors" :key="field">{{ message }}</p>
        </div>

        <!-- Meta row: post type + tags -->
        <div class="flex flex-wrap items-center gap-3 px-1">
            <div v-if="postTypes.length" class="flex items-center gap-2 shrink-0">
                <span class="text-xs text-muted uppercase tracking-wide shrink-0">{{ t("admin.posts.postType") }}</span>
                <div class="min-w-40">
                    <AppSelect v-model="form.postTypeId">
                        <option v-for="postType in postTypes" :key="postType.id" :value="String(postType.id)">
                            {{ postType.label }}
                        </option>
                    </AppSelect>
                </div>
            </div>
            <!-- Tags inline chips -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs text-muted uppercase tracking-wide shrink-0">{{ t("admin.posts.tags") }}</span>
                <label
                    v-for="tag in allTags"
                    :key="tag.id"
                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium cursor-pointer border transition-colors select-none"
                    :class="form.tagIds.includes(tag.id)
                        ? 'bg-indigo-600 border-indigo-600 text-white'
                        : 'bg-surface-2 border-line text-secondary hover:border-indigo-400 hover:text-primary'"
                >
                    <input type="checkbox" class="sr-only" :checked="form.tagIds.includes(tag.id)" v-on:change="toggleTag(tag.id)">
                    {{ tag.name }}
                </label>
                <p v-if="!allTags.length" class="text-xs text-muted italic">{{ t("admin.tags.empty") }}</p>
            </div>
        </div>

        <!-- Locale tabs -->
        <div class="flex gap-1 border-b border-line overflow-x-auto scrollbar-hide">
            <button
                v-for="locale in locales"
                :key="locale"
                type="button"
                class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px whitespace-nowrap shrink-0"
                :class="activeLocale === locale
                    ? 'border-indigo-500 text-indigo-400'
                    : 'border-transparent text-secondary hover:text-primary'"
                v-on:click="switchLocale(locale)"
            >
                {{ t("locales." + locale) }}
            </button>
        </div>

        <!-- Translation fields -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <AppInput
                v-model="form.translations[activeLocale].title"
                :label="t('admin.posts.title')"
                :placeholder="t('admin.posts.titlePlaceholder')"
            />
            <AppInput
                v-model="form.translations[activeLocale].slug"
                :label="t('admin.posts.slug')"
                :placeholder="t('admin.posts.slugPlaceholder')"
            />
        </div>

        <!-- Editor.js -->
        <div>
            <label class="block text-xs text-secondary uppercase tracking-wide mb-2">
                {{ t("admin.posts.blocks") }}
            </label>
            <div class="rounded-xl border border-line bg-surface shadow-sm">
                <EditorBlock
                    :key="`${activeLocale}-${editorKey}`"
                    v-model="form.translations[activeLocale].blocks"
                />
            </div>
        </div>

        <!-- SEO -->
        <div class="border-t border-line pt-4 space-y-3">
            <p class="text-xs font-semibold text-secondary uppercase tracking-wide">SEO</p>
            <AppInput
                v-model="form.translations[activeLocale].metaTitle"
                :label="t('admin.posts.metaTitle')"
            />
            <AppTextarea
                v-model="form.translations[activeLocale].metaDescription"
                :label="t('admin.posts.metaDescription')"
                :rows="3"
            />
        </div>
    </div>

    <!-- Templates panel -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showTemplates" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="w-full max-w-2xl bg-surface rounded-2xl border border-line shadow-2xl flex flex-col max-h-[80vh]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-line shrink-0">
                        <div>
                            <h2 class="text-base font-semibold text-primary">{{ t("admin.editor.templates.title") }}</h2>
                            <p class="text-xs text-muted mt-0.5">{{ t("admin.editor.templates.subtitle") }}</p>
                        </div>
                        <button type="button" class="p-1.5 rounded-lg text-secondary hover:text-primary hover:bg-surface-2 transition-colors" v-on:click="showTemplates = false">
                            <X class="w-5 h-5" :stroke-width="2" />
                        </button>
                    </div>
                    <!-- Grid -->
                    <div class="overflow-y-auto p-6 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button
                            v-for="template in TEMPLATES"
                            :key="template.id"
                            type="button"
                            class="text-left p-4 rounded-xl border border-line bg-surface-2 hover:border-indigo-500 hover:bg-indigo-500/5 transition-all group"
                            v-on:click="applyTemplate(template)"
                        >
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-2xl">{{ template.icon }}</span>
                                <span class="font-medium text-primary text-sm group-hover:text-indigo-400 transition-colors">{{ t("admin.editor.templates." + template.id + ".label") }}</span>
                            </div>
                            <p class="text-xs text-muted">{{ t("admin.editor.templates." + template.id + ".description") }}</p>
                            <p class="text-xs text-muted/60 mt-2">{{ template.blocks.length }} {{ template.blocks.length > 1 ? t("admin.editor.templates.blocks") : t("admin.editor.templates.block") }}</p>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Preview overlay -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showPreview" class="fixed inset-0 z-50 flex flex-col bg-bg overflow-y-auto">
                <!-- Preview header -->
                <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-3 border-b border-line bg-surface/90 backdrop-blur-sm shrink-0">
                    <span class="text-sm font-medium text-secondary">
                        {{ t("admin.posts.preview") }} — {{ t("locales." + activeLocale) }}
                    </span>
                    <button
                        type="button"
                        class="p-1.5 rounded-lg text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                        v-on:click="showPreview = false"
                    >
                        <X class="w-5 h-5" :stroke-width="2" />
                    </button>
                </div>

                <!-- Preview content -->
                <div class="flex-1 w-full max-w-3xl mx-auto px-6 py-12">
                    <h1 v-if="form.translations[activeLocale]?.title" class="text-3xl font-bold text-primary mb-8">
                        {{ form.translations[activeLocale].title }}
                    </h1>
                    <div
                        v-if="previewHtml"
                        class="prose-preview"
                        v-html="previewHtml"
                    />
                    <p v-else class="text-muted text-sm italic">
                        {{ t("admin.posts.previewEmpty") }}
                    </p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
