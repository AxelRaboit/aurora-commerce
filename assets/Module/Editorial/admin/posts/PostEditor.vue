<script setup>
import { ref, reactive, computed, onMounted, watch, provide, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { ArrowLeft, Save, Eye, X, LayoutTemplate, Lock, Unlock, Merge, History, ExternalLink } from "lucide-vue-next";
import { renderBlocks } from "@/shared/utils/data/blocksRenderer.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppDatePicker from "@/shared/components/form/AppDatePicker.vue";
import AppMessage from "@/shared/components/feedback/AppMessage.vue";
import AppCheckbox from "@/shared/components/form/AppCheckbox.vue";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import EditorBlock from "@/shared/components/display/EditorBlock.vue";
import PreviewOverlay from "@/shared/components/overlay/PreviewOverlay.vue";
import PostPreviewOverlay from "./PostPreviewOverlay.vue";
import ConflictMergeOverlay from "./ConflictMergeOverlay.vue";
import RevisionsOverlay from "./RevisionsOverlay.vue";
import PostCustomField from "./PostCustomField.vue";
import PostSeoPanel from "./PostSeoPanel.vue";
import PostTaxonomiesPanel from "./PostTaxonomiesPanel.vue";
import PostFeaturedImagePanel from "./PostFeaturedImagePanel.vue";
import PostTemplatesOverlay from "./PostTemplatesOverlay.vue";
import { usePostSave } from "./composables/usePostSave.js";
import { useConflictResolution } from "./composables/useConflictResolution.js";
import { useRelatedSearch } from "./composables/useRelatedSearch.js";
import { applyPostData } from "./utils/applyPostData.js";
import { PostStatus, POST_STATUS_VALUES } from "@editorial/utils/postStatus.js";
import { useSlugLock } from "@/shared/composables/form/useSlugLock.js";
import { useKeyboardShortcut } from "@/shared/composables/useKeyboardShortcut.js";
import { statusBadge } from "@/shared/utils/format/statusStyles.js";
import { DEFAULT_LOCALES } from "@/shared/utils/lang.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";

const { t } = useI18n();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    postId: { type: Number, default: null },
    postTypes: { type: Array, default: () => [] },
    taxonomies: { type: Array, default: () => [] },
    locales: { type: Array, default: () => DEFAULT_LOCALES },
    showPath: { type: String, required: true },
    createPath: { type: String, required: true },
    editPath: { type: String, required: true },
});

const emit = defineEmits(["saved", "back"]);

const activeLocale = ref(props.locales[0] ?? "fr");
const fetching = ref(false);

const {
    version,
    baseTranslations,
    remotePost,
    remoteLoading,
    showMerge,
    mergeRemoteTranslations,
    snapshotBase,
    openRemoteVersion,
    closeRemoteVersion,
    openMerge,
    closeMerge,
} = useConflictResolution({ showPath: props.showPath, postId: computed(() => props.postId) });

let flushEditor      = null;
let renderEditorBlocks = null;
provide("registerEditorFlush",  (fn) => { flushEditor       = fn; });
provide("registerEditorRender", (fn) => { renderEditorBlocks = fn; });

function makeEmptyTranslation() {
    return {
        title: "",
        slug: "",
        blocks: [],
        metaTitle: "",
        metaDescription: "",
        customFields: {},
        ogImageMediaId: null,
        ogImageUrl: null,
        ogImageFocalPosition: "50% 50%",
        canonicalUrl: "",
        noindex: false,
        focusKeyword: "",
        jsonLd: null,
    };
}

const postTypeOptions = computed(() =>
    props.postTypes.map((pt) => ({ value: String(pt.id), label: pt.label })),
);

const form = reactive({
    postTypeId: String(props.postTypes[0]?.id ?? ""),
    status: PostStatus.Draft,
    scheduledAt: "",
    featuredMediaId: null,
    termIds: [],
    translations: Object.fromEntries(props.locales.map((locale) => [locale, makeEmptyTranslation()])),
    relatedPostIds: [],
    commentsEnabled: true,
});

const publishedAt = ref(null);
const trashed = ref(false);

// ── Related posts ────────────────────────────────────────────────────────────
const {
    query: relatedSearchQuery,
    results: relatedSearchResults,
    loading: relatedSearchLoading,
    open: relatedSearchOpen,
    selected: relatedPosts,
    add: addRelatedPost,
    remove: removeRelatedPost,
    setSelected: setRelatedPosts,
} = useRelatedSearch({
    excludeId: props.postId,
    getSelectedIds: () => form.relatedPostIds,
    addId: (id) => form.relatedPostIds.push(id),
    removeId: (id) => { form.relatedPostIds = form.relatedPostIds.filter((existing) => existing !== id); },
});

// ── Slug lock ────────────────────────────────────────────────────────────────
const { locked: slugLocked, toggle: toggleSlugLock } = useSlugLock({
    getTitle: () => form.translations[activeLocale.value]?.title ?? "",
    setSlug: (value) => {
        const tr = form.translations[activeLocale.value];
        if (tr) tr.slug = value;
    },
});

async function switchLocale(locale) {
    if (locale === activeLocale.value) return;
    await flushEditor?.();
    activeLocale.value = locale;
}

// ── Dirty state ──────────────────────────────────────────────────────────────
const isDirty = ref(false);
watch(form, () => { isDirty.value = true; }, { deep: true });

// ── Featured image ───────────────────────────────────────────────────────────
const featuredMediaUrl = ref(null);
const featuredMediaFocalPosition = ref("50% 50%");

const activeTranslation = computed(() => form.translations[activeLocale.value] ?? null);

// ── Keyboard shortcut Ctrl+S ─────────────────────────────────────────────────
useKeyboardShortcut({ key: "s", ctrl: true }, () => handleSave());

const sideState = {
    publishedAt,
    trashed,
    featuredMediaUrl,
    featuredMediaFocalPosition,
    relatedPosts,
    version,
};

onMounted(async () => {
    if (!props.postId) return;
    fetching.value = true;
    try {
        const response = await fetch(props.showPath.replace("__id__", props.postId));
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success) {
            applyPostData(data.post, form, sideState);
            setRelatedPosts(data.post.relatedPosts ?? []);
            snapshotBase(form.translations);
        }
    } catch {
        toast.error(t("shared.common.error"));
        emit("back");
    } finally {
        fetching.value = false;
        nextTick(() => { isDirty.value = false; });
    }
});

function toggleTerm(termId) {
    const index = form.termIds.indexOf(termId);
    if (index === -1) {
        form.termIds.push(termId);
    } else {
        form.termIds.splice(index, 1);
    }
}

// ── Taxonomy picker helpers ──────────────────────────────────────────────────
const availableTaxonomies = computed(() => {
    const currentPostTypeId = Number(form.postTypeId);
    if (!currentPostTypeId) return [];
    return (props.taxonomies ?? []).filter((tx) => (tx.postTypeIds ?? []).includes(currentPostTypeId));
});

// ── Custom fields (defined on the current PostType) ──────────────────────────
const customFieldsDefs = computed(() => {
    const currentPostTypeId = Number(form.postTypeId);
    const pt = props.postTypes.find((postType) => postType.id === currentPostTypeId);
    if (!pt) return [];
    return [...(pt.fields ?? [])].sort((a, b) => a.position - b.position);
});

const currentPostTypeSlug = computed(() => {
    const currentPostTypeId = Number(form.postTypeId);
    const pt = props.postTypes.find((postType) => postType.id === currentPostTypeId);
    return pt?.slug ?? "";
});

const frontUrl = computed(() => {
    const slug = form.translations[activeLocale.value]?.slug;
    if (!slug || !currentPostTypeSlug.value) return null;
    return `/${activeLocale.value}/${currentPostTypeSlug.value}/${slug}`;
});

function defaultValueForField(field) {
    if (field.type === "checkbox") return false;
    if (field.type === "reference" && field.options?.multiple === true) return [];
    return null;
}

function ensureCustomFieldsForLocale(locale) {
    const translation = form.translations[locale];
    if (!translation) return;
    for (const field of customFieldsDefs.value) {
        if (!(field.name in translation.customFields)) {
            translation.customFields[field.name] = defaultValueForField(field);
        }
    }
}

watch(
    [customFieldsDefs, activeLocale],
    () => ensureCustomFieldsForLocale(activeLocale.value),
    { immediate: true },
);

const showPreview   = ref(false);
const showTemplates = ref(false);
const showRevisions = ref(false);
const editorKey     = ref(0);

async function reloadAfterRestore() {
    showRevisions.value = false;
    try {
        const response = await fetch(props.showPath.replace("__id__", props.postId));
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success) {
            applyPostData(data.post, form, sideState);
            setRelatedPosts(data.post.relatedPosts ?? []);
            snapshotBase(form.translations);
            if (renderEditorBlocks && form.translations[activeLocale.value]?.blocks) {
                await nextTick();
                await renderEditorBlocks(form.translations[activeLocale.value].blocks);
            } else {
                editorKey.value++;
            }
            nextTick(() => { isDirty.value = false; });
        }
    } catch {
        toast.error(t("shared.common.error"));
    }
}

// ── Template panel ───────────────────────────────────────────────────────────
async function applyTemplate(template) {
    const blocks = structuredClone(template.blocks);
    showTemplates.value = false;
    if (renderEditorBlocks) {
        await renderEditorBlocks(blocks);
    } else {
        form.translations[activeLocale.value].blocks = blocks;
        editorKey.value++;
    }
}

const previewHtml = computed(() =>
    renderBlocks(form.translations[activeLocale.value]?.blocks ?? []),
);

const { loading, errors, conflict, save: savePost } = usePostSave(
    props.createPath,
    props.editPath,
    (post) => {
        toast.success(props.postId ? t("admin.posts.updated") : t("admin.posts.created"));
        version.value = post.version ?? null;
        snapshotBase(form.translations);
        emit("saved", post, !props.postId);
    },
);

async function handleSave({ force = false } = {}) {
    await flushEditor?.();
    const success = await savePost(props.postId, {
        postTypeId: Number(form.postTypeId),
        status: form.status,
        scheduledAt: form.status === PostStatus.Scheduled && form.scheduledAt ? form.scheduledAt : null,
        featuredMediaId: form.featuredMediaId,
        termIds: form.termIds,
        relatedPostIds: form.relatedPostIds,
        commentsEnabled: form.commentsEnabled,
        translations: form.translations,
        version: version.value,
        force,
    });
    if (success) isDirty.value = false;
}

async function applyMergeResolution(resolvedBlocksByLocale) {
    for (const [locale, blocks] of Object.entries(resolvedBlocksByLocale)) {
        if (form.translations[locale]) {
            form.translations[locale].blocks = blocks;
        }
    }
    closeMerge();
    await nextTick();
    if (renderEditorBlocks && form.translations[activeLocale.value]?.blocks) {
        await renderEditorBlocks(form.translations[activeLocale.value].blocks);
    }
    await handleSave({ force: true });
}

function forceSave() {
    handleSave({ force: true });
}
</script>

<template>
    <div v-if="fetching" class="flex items-center justify-center py-20 text-secondary text-sm">
        {{ t("shared.common.loading") }}
    </div>

    <div v-else class="space-y-6">
        <AppMessage v-if="trashed" variant="trash">
            {{ t("admin.posts.trashedBanner") }}
        </AppMessage>

        <AppMessage v-if="conflict" variant="warning">
            {{ t("admin.posts.conflict") }}
            <template #actions>
                <AppButton variant="secondary" size="sm" v-on:click="openRemoteVersion">
                    <Eye class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("admin.posts.conflictCompare") }}
                </AppButton>
                <AppButton variant="primary" size="sm" :loading="remoteLoading" v-on:click="openMerge">
                    <Merge class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("admin.posts.conflictMerge") }}
                </AppButton>
                <AppButton variant="danger" size="sm" :loading="loading" v-on:click="forceSave">
                    <Save class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t("admin.posts.conflictForce") }}
                </AppButton>
            </template>
        </AppMessage>

        <div class="space-y-3">
            <div class="flex items-center gap-3 min-w-0">
                <AppButton variant="ghost" size="none" class="p-2 shrink-0" v-on:click="$emit('back')">
                    <ArrowLeft class="w-5 h-5" :stroke-width="2" />
                </AppButton>
                <h1 class="flex-1 text-lg font-semibold text-primary truncate min-w-0">
                    {{ postId ? t("admin.posts.edit") : t("admin.posts.add") }}
                </h1>
            </div>

            <div class="grid grid-cols-1 sm:flex sm:flex-wrap sm:items-center gap-2">
                <AppSelect v-model="form.status" class="w-full sm:w-auto">
                    <option v-for="value in POST_STATUS_VALUES" :key="value" :value="value">{{ t(`admin.posts.statusOptions.${value}`) }}</option>
                </AppSelect>
                <AppDatePicker
                    v-if="form.status === PostStatus.Scheduled"
                    v-model="form.scheduledAt"
                    :enable-time="true"
                    class="w-full sm:w-auto"
                />
                <AppButton variant="secondary" size="md" class="w-full sm:w-auto" v-on:click="showTemplates = true">
                    <LayoutTemplate class="w-4 h-4" :stroke-width="2" />
                    <span>Templates</span>
                </AppButton>
                <AppButton
                    v-if="postId"
                    variant="secondary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="showRevisions = true"
                >
                    <History class="w-4 h-4" :stroke-width="2" />
                    <span>{{ t("admin.posts.revisions.title") }}</span>
                </AppButton>
                <AppButton variant="secondary" size="md" class="w-full sm:w-auto" v-on:click="showPreview = true">
                    <Eye class="w-4 h-4" :stroke-width="2" />
                    <span>{{ t("admin.posts.preview") }}</span>
                </AppButton>
                <AppButton
                    v-if="frontUrl && form.status === 'published'"
                    variant="secondary"
                    size="md"
                    class="w-full sm:w-auto"
                    :href="frontUrl"
                    target="_blank"
                >
                    <ExternalLink class="w-4 h-4" :stroke-width="2" />
                    <span>{{ t("shared.common.view") }}</span>
                </AppButton>
                <AppButton
                    variant="primary"
                    size="md"
                    class="relative w-full sm:w-auto"
                    :loading="loading"
                    v-on:click="handleSave"
                >
                    <Save v-if="!loading" class="w-4 h-4" :stroke-width="2" />
                    <span>{{ t("shared.common.save") }}</span>
                    <span v-if="isDirty && !loading" class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-amber-400 border-2 border-white dark:border-surface" />
                </AppButton>
            </div>
        </div>

        <AppMessage v-if="Object.keys(errors).length" variant="danger">
            <p v-for="(message, field) in errors" :key="field">{{ message }}</p>
        </AppMessage>

        <p v-if="postId" class="px-1 text-xs text-muted font-mono">ID : {{ postId }}</p>

        <p v-if="publishedAt" class="px-1 text-xs text-muted">
            {{ t("admin.posts.publishedAt") }} {{ formatDateTime(publishedAt) }}
        </p>

        <div class="flex flex-col gap-3 px-1">
            <div v-if="postTypes.length" class="flex items-center gap-2 shrink-0">
                <span class="text-xs text-muted uppercase tracking-wide shrink-0">{{ t("admin.posts.postType") }}</span>
                <div class="min-w-40">
                    <AppMultiselect
                        v-model="form.postTypeId"
                        :options="postTypeOptions"
                        track-by="value"
                        option-label="label"
                    />
                </div>
            </div>
            <PostTaxonomiesPanel
                :taxonomies="availableTaxonomies"
                :selected-term-ids="form.termIds"
                :active-locale="activeLocale"
                :default-locale="locales[0]"
                v-on:toggle-term="toggleTerm"
            />
        </div>

        <div class="px-1">
            <AppToggle
                :model-value="form.commentsEnabled"
                :label="t('admin.posts.commentsEnabled')"
                v-on:update:model-value="form.commentsEnabled = $event"
            />
        </div>

        <div class="flex flex-col gap-2 px-1">
            <span class="text-xs text-muted uppercase tracking-wide">{{ t("admin.posts.relatedPosts.title") }}</span>

            <div v-if="relatedPosts.length" class="flex flex-col gap-1.5">
                <div
                    v-for="related in relatedPosts"
                    :key="related.id"
                    class="flex items-center gap-2 px-3 py-2 rounded-md bg-surface border border-line/60"
                >
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium" :class="statusBadge(related.status)">
                        {{ t("admin.stats.postStatus." + related.status) }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm text-primary truncate">{{ related.title ?? "(—)" }}</div>
                        <div class="text-xs text-muted truncate">{{ related.postType }}</div>
                    </div>
                    <AppIconButton color="rose" v-on:click="removeRelatedPost(related.id)">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                    </AppIconButton>
                </div>
            </div>

            <div class="relative">
                <AppInput
                    v-model="relatedSearchQuery"
                    :placeholder="t('admin.posts.relatedPosts.searchPlaceholder')"
                    v-on:focus="relatedSearchOpen = true; runRelatedSearch()"
                    v-on:blur="setTimeout(() => { relatedSearchOpen = false; }, 150)"
                />
                <div
                    v-if="relatedSearchOpen && (relatedSearchResults.length || relatedSearchLoading)"
                    class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto scrollbar-thin rounded-md border border-line bg-surface shadow-lg"
                >
                    <div v-if="relatedSearchLoading" class="px-3 py-2 text-xs text-muted">{{ t("shared.common.loading") }}</div>
                    <button
                        v-for="result in relatedSearchResults"
                        :key="result.id"
                        type="button"
                        class="w-full text-left px-3 py-2 hover:bg-surface-2 transition-colors flex items-center gap-2"
                        v-on:mousedown.prevent="addRelatedPost(result)"
                    >
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium" :class="statusBadge(result.status)">
                            {{ t("admin.stats.postStatus." + result.status) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-primary truncate">{{ result.title ?? "(—)" }}</div>
                            <div class="text-xs text-muted truncate">{{ result.postType }}</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <PostFeaturedImagePanel
            v-model:media-id="form.featuredMediaId"
            v-model:media-url="featuredMediaUrl"
            v-model:focal-position="featuredMediaFocalPosition"
        />

        <div class="flex gap-1 border-b border-line overflow-x-auto scrollbar-hide">
            <button
                v-for="locale in locales"
                :key="locale"
                type="button"
                class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px whitespace-nowrap shrink-0"
                :class="activeLocale === locale
                    ? 'border-accent-500 text-accent-400'
                    : 'border-transparent text-secondary hover:text-primary'"
                v-on:click="switchLocale(locale)"
            >
                {{ t("shared.locales." + locale) }}
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <AppInput
                v-model="form.translations[activeLocale].title"
                :label="t('admin.posts.title')"
                :placeholder="t('admin.posts.titlePlaceholder')"
            />
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <AppInput
                        v-model="form.translations[activeLocale].slug"
                        :label="t('admin.posts.slug')"
                        :placeholder="t('admin.posts.slugPlaceholder')"
                        :readonly="slugLocked"
                    />
                </div>
                <AppButton
                    variant="secondary"
                    size="none"
                    class="p-2 mb-0.5 shrink-0"
                    :title="slugLocked ? t('admin.posts.slugUnlock') : t('admin.posts.slugLock')"
                    v-on:click="toggleSlugLock"
                >
                    <Lock v-if="slugLocked" class="w-4 h-4" :stroke-width="2" />
                    <Unlock v-else class="w-4 h-4" :stroke-width="2" />
                </AppButton>
            </div>
            <p v-if="frontUrl" class="text-xs text-muted">
                <span class="text-secondary">URL :</span>
                <a v-if="form.status === 'published'" :href="frontUrl" target="_blank" class="ml-1 font-mono text-accent-400 hover:underline break-all">{{ frontUrl }}</a>
                <span v-else class="ml-1 font-mono text-muted break-all">{{ frontUrl }}</span>
            </p>
        </div>

        <div v-if="customFieldsDefs.length" class="border-t border-line pt-4 space-y-3">
            <p class="text-xs font-semibold text-secondary uppercase tracking-wide">{{ t("admin.posts.customFields") }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <PostCustomField
                    v-for="field in customFieldsDefs"
                    :key="field.id"
                    :field="field"
                    :model-value="form.translations[activeLocale].customFields[field.name]"
                    v-on:update:model-value="form.translations[activeLocale].customFields[field.name] = $event"
                />
            </div>
        </div>

        <PostSeoPanel
            v-if="form.translations[activeLocale]"
            :translation="form.translations[activeLocale]"
            :locale="activeLocale"
            :post-type-slug="currentPostTypeSlug"
            :published-at="publishedAt"
        />

        <div class="border-t border-line pt-4">
            <label class="block text-xs text-secondary uppercase tracking-wide mb-2">
                {{ t("admin.posts.blocks") }}
            </label>
            <div class="rounded-xl border border-line bg-surface shadow-sm">
                <EditorBlock
                    :key="`${activeLocale}-${editorKey}`"
                    v-model="form.translations[activeLocale].blocks"
                    :post-types="postTypes"
                />
            </div>
        </div>
    </div>

    <PostTemplatesOverlay :show="showTemplates" v-on:close="showTemplates = false" v-on:apply="applyTemplate" />

    <PreviewOverlay
        :show="showPreview"
        :title="form.translations[activeLocale]?.title"
        :html="previewHtml"
        :featured-media-url="featuredMediaUrl"
        :label="t('admin.posts.preview') + ' - ' + t('shared.locales.' + activeLocale)"
        v-on:close="showPreview = false"
    />

    <PostPreviewOverlay
        :post="remotePost"
        :loading="remoteLoading"
        :locales="locales"
        v-on:close="closeRemoteVersion"
    />

    <ConflictMergeOverlay
        :show="showMerge"
        :base="baseTranslations"
        :local="form.translations"
        :remote="mergeRemoteTranslations ?? {}"
        :locales="locales"
        v-on:close="closeMerge"
        v-on:apply="applyMergeResolution"
    />

    <RevisionsOverlay
        v-if="postId"
        :post-id="postId"
        :show="showRevisions"
        :locales="locales"
        :current-translations="form.translations"
        v-on:close="showRevisions = false"
        v-on:restored="reloadAfterRestore"
    />
</template>
