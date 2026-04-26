<script setup>
import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch, provide, nextTick } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { ArrowLeft, Save, Eye, X, LayoutTemplate, Lock, Unlock, ImagePlus, Merge, History, ExternalLink } from "lucide-vue-next";
import { renderBlocks } from "@/shared/utils/blocksRenderer.js";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppMessage from "@/shared/components/AppMessage.vue";
import AppCheckbox from "@/shared/components/AppCheckbox.vue";
import AppToggle from "@/shared/components/AppToggle.vue";
import AppTextarea from "@/shared/components/AppTextarea.vue";
import AppSelect from "@/shared/components/AppSelect.vue";
import AppMultiselect from "@/shared/components/AppMultiselect.vue";
import EditorBlock from "@/shared/components/EditorBlock.vue";
import PreviewOverlay from "@/shared/components/PreviewOverlay.vue";
import PostPreviewOverlay from "./PostPreviewOverlay.vue";
import ConflictMergeOverlay from "./ConflictMergeOverlay.vue";
import RevisionsOverlay from "./RevisionsOverlay.vue";
import GoogleSerpPreview from "./GoogleSerpPreview.vue";
import PostCustomField from "./PostCustomField.vue";
import { usePostSave } from "./composables/usePostSave.js";
import { useConflictResolution } from "./composables/useConflictResolution.js";
import { TEMPLATES } from "@/shared/utils/editorjs/templates.js";
import { slugify } from "@/shared/utils/slugify.js";
import { statusBadge } from "@/shared/utils/statusStyles.js";
import { DEFAULT_LOCALES } from "@/shared/utils/lang.js";
import { useDateFormat } from "@/shared/composables/useDateFormat.js";

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
    status: "draft",
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
const relatedPosts = ref([]);
const relatedSearchQuery = ref("");
const relatedSearchResults = ref([]);
const relatedSearchLoading = ref(false);
const relatedSearchOpen = ref(false);

watch(relatedSearchQuery, useDebounce(runRelatedSearch, 200));

async function runRelatedSearch() {
    relatedSearchLoading.value = true;
    try {
        const url = new URL("/admin/posts/search", window.location.origin);
        if (relatedSearchQuery.value) url.searchParams.set("q", relatedSearchQuery.value);
        if (props.postId) url.searchParams.set("excludeId", String(props.postId));
        const response = await fetch(url);
        if (!response.ok) throw new Error();
        const data = await response.json();
        relatedSearchResults.value = (data.results ?? []).filter(
            (result) => !form.relatedPostIds.includes(result.id),
        );
    } catch {
        relatedSearchResults.value = [];
    } finally {
        relatedSearchLoading.value = false;
    }
}

function addRelatedPost(result) {
    if (form.relatedPostIds.includes(result.id)) return;
    form.relatedPostIds.push(result.id);
    relatedPosts.value.push(result);
    relatedSearchQuery.value = "";
    relatedSearchResults.value = relatedSearchResults.value.filter((r) => r.id !== result.id);
}

function removeRelatedPost(id) {
    form.relatedPostIds = form.relatedPostIds.filter((existing) => existing !== id);
    relatedPosts.value = relatedPosts.value.filter((r) => r.id !== id);
}

// ── Slug lock ────────────────────────────────────────────────────────────────
const slugLocked = ref(true);

watch(
    () => form.translations[activeLocale.value]?.title,
    (newTitle) => {
        const translation = form.translations[activeLocale.value];
        if (translation && slugLocked.value) translation.slug = slugify(newTitle);
    },
);

function toggleSlugLock() {
    slugLocked.value = !slugLocked.value;
    if (slugLocked.value) {
        const translation = form.translations[activeLocale.value];
        if (translation) translation.slug = slugify(translation.title);
    }
}

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
const uploadingFeatured = ref(false);
const featuredInputRef = ref(null);
const previewFeatured = ref(false);

async function uploadFeaturedImage(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    uploadingFeatured.value = true;
    try {
        const body = new FormData();
        body.append("image", file);
        const response = await fetch("/admin/media/upload", { method: HttpMethod.Post, body });
        if (!response.ok) throw new Error();
        const data = await response.json();
        if (data.success) {
            form.featuredMediaId = data.file?.id ?? null;
            featuredMediaUrl.value = data.file?.url ?? null;
            featuredMediaFocalPosition.value = data.media?.focalPositionCss ?? "50% 50%";
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        uploadingFeatured.value = false;
        if (featuredInputRef.value) featuredInputRef.value.value = "";
    }
}

function removeFeaturedImage() {
    form.featuredMediaId = null;
    featuredMediaUrl.value = null;
}

// ── SEO counters ─────────────────────────────────────────────────────────────
const metaTitleLength = computed(() => form.translations[activeLocale.value]?.metaTitle?.length ?? 0);
const metaDescLength  = computed(() => form.translations[activeLocale.value]?.metaDescription?.length ?? 0);

function seoCounterClass(length, max) {
    if (length === 0)          return "text-muted";
    if (length <= max * 0.85)  return "text-green-500";
    if (length <= max)         return "text-amber-500";
    return "text-red-500";
}

// ── SEO quality checks ───────────────────────────────────────────────────────
const activeTranslation = computed(() => form.translations[activeLocale.value] ?? null);

function containsCI(haystack, needle) {
    if (!haystack || !needle) return false;
    return haystack.toLowerCase().includes(needle.toLowerCase());
}

const focusChecks = computed(() => {
    const tr = activeTranslation.value;
    const keyword = tr?.focusKeyword?.trim() ?? "";
    if (!keyword || !tr) return [];
    return [
        { key: "title", ok: containsCI(tr.title, keyword) },
        { key: "metaTitle", ok: containsCI(tr.metaTitle, keyword) },
        { key: "metaDescription", ok: containsCI(tr.metaDescription, keyword) },
        { key: "slug", ok: containsCI(tr.slug, keyword) },
    ];
});

// ── JSON-LD helpers ──────────────────────────────────────────────────────────
const jsonLdText = ref("");
const jsonLdError = ref(null);

watch(
    () => activeTranslation.value?.jsonLd,
    (value) => {
        jsonLdText.value = value ? JSON.stringify(value, null, 2) : "";
        jsonLdError.value = null;
    },
    { immediate: true },
);

watch(jsonLdText, (raw) => {
    const tr = activeTranslation.value;
    if (!tr) return;
    const trimmed = raw.trim();
    if ("" === trimmed) {
        tr.jsonLd = null;
        jsonLdError.value = null;
        return;
    }
    try {
        const parsed = JSON.parse(trimmed);
        if (typeof parsed !== "object" || parsed === null || Array.isArray(parsed)) {
            jsonLdError.value = t("admin.posts.seo.jsonLdMustBeObject");
            return;
        }
        tr.jsonLd = parsed;
        jsonLdError.value = null;
    } catch (err) {
        jsonLdError.value = err.message;
    }
});

function generateArticleJsonLd() {
    const tr = activeTranslation.value;
    if (!tr) return;
    const template = {
        "@context": "https://schema.org",
        "@type": "Article",
        headline: tr.title || "",
        description: tr.metaDescription || "",
        image: tr.ogImageUrl ? [tr.ogImageUrl] : undefined,
        datePublished: publishedAt.value ?? undefined,
    };
    // Strip undefined entries
    for (const key of Object.keys(template)) {
        if (template[key] === undefined) delete template[key];
    }
    tr.jsonLd = template;
    jsonLdText.value = JSON.stringify(template, null, 2);
    jsonLdError.value = null;
}

// ── OG image upload ──────────────────────────────────────────────────────────
const ogInputRef = ref(null);
const uploadingOg = ref(false);

async function uploadOgImage(event) {
    const file = event.target.files?.[0];
    const tr = activeTranslation.value;
    if (!file || !tr) return;
    uploadingOg.value = true;
    try {
        const body = new FormData();
        body.append("image", file);
        const response = await fetch("/admin/media/upload", { method: HttpMethod.Post, body });
        if (!response.ok) throw new Error();
        const data = await response.json();
        if (data.success) {
            tr.ogImageMediaId = data.file?.id ?? null;
            tr.ogImageUrl = data.file?.url ?? null;
            tr.ogImageFocalPosition = data.media?.focalPositionCss ?? "50% 50%";
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        uploadingOg.value = false;
        if (ogInputRef.value) ogInputRef.value.value = "";
    }
}

function removeOgImage() {
    const tr = activeTranslation.value;
    if (!tr) return;
    tr.ogImageMediaId = null;
    tr.ogImageUrl = null;
}

// ── Keyboard shortcut Ctrl+S ─────────────────────────────────────────────────
function onKeydown(event) {
    if ((event.ctrlKey || event.metaKey) && event.key === "s") {
        event.preventDefault();
        handleSave();
    }
}

onMounted(async () => {
    window.addEventListener("keydown", onKeydown);
    if (!props.postId) return;
    fetching.value = true;
    try {
        const response = await fetch(props.showPath.replace("__id__", props.postId));
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success) {
            form.postTypeId = String(data.post.postType.id);
            form.status = data.post.status;
            form.scheduledAt = data.post.scheduledAt ? data.post.scheduledAt.slice(0, 16) : "";
            publishedAt.value = data.post.publishedAt ?? null;
            trashed.value = data.post.trashed ?? false;
            form.featuredMediaId = data.post.featuredMediaId ?? null;
            featuredMediaUrl.value = data.post.featuredMediaUrl ?? null;
            featuredMediaFocalPosition.value = data.post.featuredMediaFocalPosition ?? "50% 50%";
            form.termIds = [...(data.post.termIds ?? [])];
            form.relatedPostIds = [...(data.post.relatedPostIds ?? [])];
            form.commentsEnabled = data.post.commentsEnabled ?? true;
            relatedPosts.value = [...(data.post.relatedPosts ?? [])];
            version.value = data.post.version ?? null;
            for (const [locale, translation] of Object.entries(data.post.translations ?? {})) {
                if (form.translations[locale]) {
                    Object.assign(form.translations[locale], translation);
                }
            }
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

onBeforeUnmount(() => {
    window.removeEventListener("keydown", onKeydown);
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

function taxonomyLabel(taxonomy) {
    return taxonomy.translations?.[activeLocale.value]?.label
        ?? taxonomy.translations?.[props.locales[0]]?.label
        ?? taxonomy.slug;
}

function termLabel(term) {
    return term.translations?.[activeLocale.value]?.name
        ?? term.translations?.[props.locales[0]]?.name
        ?? "(—)";
}

function buildTermTree(terms) {
    const byId = new Map(terms.map((term) => [term.id, { ...term, children: [] }]));
    const roots = [];
    for (const node of byId.values()) {
        if (node.parentId && byId.has(node.parentId)) {
            byId.get(node.parentId).children.push(node);
        } else {
            roots.push(node);
        }
    }
    const sortRecursive = (nodes) => {
        nodes.sort((a, b) => (a.position - b.position) || (a.id - b.id));
        nodes.forEach((n) => sortRecursive(n.children));
    };
    sortRecursive(roots);
    return roots;
}

function flattenTreeWithDepth(nodes, depth = 0) {
    const result = [];
    for (const node of nodes) {
        result.push({ ...node, depth });
        if (node.children?.length) {
            result.push(...flattenTreeWithDepth(node.children, depth + 1));
        }
    }
    return result;
}

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
            form.postTypeId = String(data.post.postType.id);
            form.status = data.post.status;
            form.scheduledAt = data.post.scheduledAt ? data.post.scheduledAt.slice(0, 16) : "";
            publishedAt.value = data.post.publishedAt ?? null;
            trashed.value = data.post.trashed ?? false;
            form.featuredMediaId = data.post.featuredMediaId ?? null;
            featuredMediaUrl.value = data.post.featuredMediaUrl ?? null;
            featuredMediaFocalPosition.value = data.post.featuredMediaFocalPosition ?? "50% 50%";
            form.termIds = [...(data.post.termIds ?? [])];
            form.relatedPostIds = [...(data.post.relatedPostIds ?? [])];
            form.commentsEnabled = data.post.commentsEnabled ?? true;
            relatedPosts.value = [...(data.post.relatedPosts ?? [])];
            version.value = data.post.version ?? null;
            for (const [locale, translation] of Object.entries(data.post.translations ?? {})) {
                if (form.translations[locale]) {
                    Object.assign(form.translations[locale], translation);
                }
            }
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

// ── Template panel state ─────────────────────────────────────────────────────
const activeCategory     = ref("all");
const confirmingTemplate = ref(null);
const hoveredTemplate    = ref(null);

const TEMPLATE_CATEGORIES = ["article", "marketing", "layout", "technique"];


const BLOCK_TYPE_TO_TOOL_NAME = {
    header:    "heading",
    paragraph: "text",
    image:     "image",
    list:      "list",
    code:      "code",
    callout:   "callout",
    delimiter: "delimiter",
    twoColumn: "twoColumn",
    mediaText: "mediaText",
    embed:     "embed",
    table:     "table",
    quote:     "quote",
    checklist: "checklist",
};

const filteredTemplates = computed(() =>
    activeCategory.value === "all"
        ? TEMPLATES
        : TEMPLATES.filter((tpl) => tpl.category === activeCategory.value),
);

function blockLabel(type) {
    const key = BLOCK_TYPE_TO_TOOL_NAME[type];
    return key ? t(`admin.editor.toolNames.${key}`) : type;
}

function closeTemplates() {
    showTemplates.value    = false;
    confirmingTemplate.value = null;
    hoveredTemplate.value  = null;
}

async function applyTemplate(template) {
    const blocks = structuredClone(template.blocks);
    closeTemplates();
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
        scheduledAt: form.status === "scheduled" && form.scheduledAt ? form.scheduledAt : null,
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
        <!-- Trashed banner -->
        <AppMessage v-if="trashed" variant="trash">
            {{ t("admin.posts.trashedBanner") }}
        </AppMessage>

        <!-- Conflict banner -->
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

        <!-- Top bar -->
        <div class="space-y-3">
            <!-- Back + title -->
            <div class="flex items-center gap-3 min-w-0">
                <AppButton variant="ghost" size="none" class="p-2 shrink-0" v-on:click="$emit('back')">
                    <ArrowLeft class="w-5 h-5" :stroke-width="2" />
                </AppButton>
                <h1 class="flex-1 text-lg font-semibold text-primary truncate min-w-0">
                    {{ postId ? t("admin.posts.edit") : t("admin.posts.add") }}
                </h1>
            </div>

            <!-- Actions: stacked on mobile, inline on desktop -->
            <div class="grid grid-cols-1 sm:flex sm:flex-wrap sm:items-center gap-2">
                <AppSelect v-model="form.status" class="w-full sm:w-auto">
                    <option value="draft">{{ t("admin.posts.statusOptions.draft") }}</option>
                    <option value="pending_review">{{ t("admin.posts.statusOptions.pending_review") }}</option>
                    <option value="scheduled">{{ t("admin.posts.statusOptions.scheduled") }}</option>
                    <option value="published">{{ t("admin.posts.statusOptions.published") }}</option>
                    <option value="archived">{{ t("admin.posts.statusOptions.archived") }}</option>
                </AppSelect>
                <AppInput
                    v-if="form.status === 'scheduled'"
                    v-model="form.scheduledAt"
                    type="datetime-local"
                    class="w-full sm:w-auto"
                    :placeholder="t('admin.posts.scheduledAt')"
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

        <!-- Global save errors -->
        <AppMessage v-if="Object.keys(errors).length" variant="danger">
            <p v-for="(message, field) in errors" :key="field">{{ message }}</p>
        </AppMessage>

        <!-- Post ID -->
        <p v-if="postId" class="px-1 text-xs text-muted font-mono">ID : {{ postId }}</p>

        <!-- Published at info -->
        <p v-if="publishedAt" class="px-1 text-xs text-muted">
            {{ t("admin.posts.publishedAt") }} {{ formatDateTime(publishedAt) }}
        </p>

        <!-- Meta row: post type + tags -->
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
            <!-- Terms picker, grouped by taxonomy -->
            <div v-for="taxonomy in availableTaxonomies" :key="taxonomy.id" class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted uppercase tracking-wide shrink-0">{{ taxonomyLabel(taxonomy) }}</span>
                    <span v-if="taxonomy.hierarchical" class="text-xs px-1.5 py-0.5 rounded bg-sky-500/15 text-sky-400">
                        {{ t("admin.taxonomies.hierarchical") }}
                    </span>
                </div>

                <!-- Flat taxonomy: chip picker -->
                <div v-if="!taxonomy.hierarchical" class="flex items-center gap-2 flex-wrap">
                    <label
                        v-for="term in taxonomy.terms"
                        :key="term.id"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium cursor-pointer border transition-colors select-none"
                        :class="form.termIds.includes(term.id)
                            ? 'bg-indigo-600 border-indigo-600 text-white'
                            : 'bg-surface-2 border-line text-secondary hover:border-indigo-400 hover:text-primary'"
                    >
                        <input type="checkbox" class="sr-only" :checked="form.termIds.includes(term.id)" v-on:change="toggleTerm(term.id)">
                        {{ termLabel(term) }}
                    </label>
                    <p v-if="!taxonomy.terms.length" class="text-xs text-muted italic">{{ t("admin.posts.termsPickerEmpty") }}</p>
                </div>

                <!-- Hierarchical taxonomy: indented checkbox tree -->
                <div v-else class="max-h-60 overflow-y-auto scrollbar-thin border border-line/60 rounded-md bg-surface-2 p-2 space-y-1">
                    <p v-if="!taxonomy.terms.length" class="text-xs text-muted italic">{{ t("admin.posts.termsPickerEmpty") }}</p>
                    <label
                        v-for="term in flattenTreeWithDepth(buildTermTree(taxonomy.terms))"
                        :key="term.id"
                        class="flex items-center gap-2 text-sm cursor-pointer hover:bg-surface-3 rounded px-1.5 py-0.5"
                        :style="{ paddingLeft: `${0.375 + term.depth * 1.25}rem` }"
                    >
                        <input
                            type="checkbox"
                            class="w-4 h-4 rounded border-line bg-surface text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"
                            :checked="form.termIds.includes(term.id)"
                            v-on:change="toggleTerm(term.id)"
                        >
                        <span class="text-primary">{{ termLabel(term) }}</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Comments toggle -->
        <div class="px-1">
            <AppToggle
                :model-value="form.commentsEnabled"
                :label="t('admin.posts.commentsEnabled')"
                v-on:update:model-value="form.commentsEnabled = $event"
            />
        </div>

        <!-- Related posts -->
        <div class="flex flex-col gap-2 px-1">
            <span class="text-xs text-muted uppercase tracking-wide">{{ t("admin.posts.relatedPosts.title") }}</span>

            <!-- Selected related posts -->
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

            <!-- Search -->
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

        <!-- Featured image -->
        <div class="flex flex-col gap-2 px-1">
            <span class="text-xs text-muted uppercase tracking-wide">{{ t("admin.posts.featuredImage") }}</span>
            <div v-if="featuredMediaUrl" class="relative group w-full h-48">
                <img
                    :src="featuredMediaUrl"
                    class="w-full h-full object-cover rounded-lg border border-line cursor-zoom-in"
                    :style="{ objectPosition: featuredMediaFocalPosition }"
                    :alt="t('admin.posts.featuredImage')"
                    v-on:click="previewFeatured = true"
                >
                <button
                    type="button"
                    class="absolute top-2 right-2 p-1 rounded-full bg-black/60 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                    v-on:click="removeFeaturedImage"
                >
                    <X class="w-4 h-4" :stroke-width="2.5" />
                </button>
            </div>
            <label
                v-else
                class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-line rounded-lg cursor-pointer hover:border-indigo-400 transition-colors"
                :class="uploadingFeatured ? 'opacity-50 pointer-events-none' : ''"
            >
                <ImagePlus class="w-6 h-6 text-muted mb-1.5" :stroke-width="1.5" />
                <span class="text-sm text-muted">{{ uploadingFeatured ? t("shared.common.loading") : t("admin.posts.addImage") }}</span>
                <input
                    ref="featuredInputRef"
                    type="file"
                    accept="image/*"
                    class="sr-only"
                    v-on:change="uploadFeaturedImage"
                >
            </label>
        </div>

        <!-- Featured image lightbox -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="previewFeatured" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" v-on:click="previewFeatured = false">
                    <img :src="featuredMediaUrl" class="max-w-[90vw] max-h-[90vh] object-contain rounded-lg shadow-2xl" :alt="t('admin.posts.featuredImage')">
                </div>
            </Transition>
        </Teleport>

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
                {{ t("shared.locales." + locale) }}
            </button>
        </div>

        <!-- Translation fields -->
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
                <a v-if="form.status === 'published'" :href="frontUrl" target="_blank" class="ml-1 font-mono text-indigo-400 hover:underline break-all">{{ frontUrl }}</a>
                <span v-else class="ml-1 font-mono text-muted break-all">{{ frontUrl }}</span>
            </p>
        </div>

        <!-- Custom fields defined on the post type -->
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

        <!-- SEO -->
        <div class="border-t border-line pt-4 space-y-4">
            <p class="text-xs font-semibold text-secondary uppercase tracking-wide">SEO</p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Left column: fields -->
                <div class="space-y-3">
                    <div>
                        <AppInput
                            v-model="form.translations[activeLocale].metaTitle"
                            :label="t('admin.posts.metaTitle')"
                        />
                        <p class="text-right text-xs mt-1" :class="seoCounterClass(metaTitleLength, 60)">
                            {{ metaTitleLength }}/60
                        </p>
                    </div>
                    <div>
                        <AppTextarea
                            v-model="form.translations[activeLocale].metaDescription"
                            :label="t('admin.posts.metaDescription')"
                            :rows="3"
                        />
                        <p class="text-right text-xs mt-1" :class="seoCounterClass(metaDescLength, 160)">
                            {{ metaDescLength }}/160
                        </p>
                    </div>
                    <AppInput
                        v-model="form.translations[activeLocale].focusKeyword"
                        :label="t('admin.posts.seo.focusKeyword')"
                        :placeholder="t('admin.posts.seo.focusKeywordPlaceholder')"
                    />
                    <AppInput
                        v-model="form.translations[activeLocale].canonicalUrl"
                        :label="t('admin.posts.seo.canonicalUrl')"
                        placeholder="https://example.com/..."
                    />
                    <AppCheckbox
                        v-model="form.translations[activeLocale].noindex"
                        :label="t('admin.posts.seo.noindex')"
                    />

                    <!-- OG image -->
                    <div>
                        <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">
                            {{ t("admin.posts.seo.ogImage") }}
                        </label>
                        <div class="flex items-center gap-3">
                            <div class="w-24 h-16 rounded-md border border-line bg-surface-2 overflow-hidden shrink-0 flex items-center justify-center">
                                <img
                                    v-if="form.translations[activeLocale].ogImageUrl"
                                    :src="form.translations[activeLocale].ogImageUrl"
                                    class="w-full h-full object-cover"
                                    :style="{ objectPosition: form.translations[activeLocale].ogImageFocalPosition ?? '50% 50%' }"
                                    alt=""
                                >
                                <ImagePlus v-else class="w-5 h-5 text-muted" :stroke-width="2" />
                            </div>
                            <div class="flex gap-2">
                                <input
                                    ref="ogInputRef"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    v-on:change="uploadOgImage"
                                >
                                <AppButton variant="secondary" size="sm" :loading="uploadingOg" v-on:click="ogInputRef?.click()">
                                    {{ t("admin.posts.seo.ogImageUpload") }}
                                </AppButton>
                                <AppButton
                                    v-if="form.translations[activeLocale].ogImageUrl"
                                    variant="ghost"
                                    size="sm"
                                    v-on:click="removeOgImage"
                                >
                                    <X class="w-3.5 h-3.5" :stroke-width="2" />
                                </AppButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right column: SERP preview + quality checks -->
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-secondary uppercase tracking-wide mb-2">{{ t("admin.posts.seo.serpPreview") }}</p>
                        <GoogleSerpPreview
                            :title="form.translations[activeLocale].metaTitle || form.translations[activeLocale].title"
                            :description="form.translations[activeLocale].metaDescription"
                            :slug="form.translations[activeLocale].slug"
                            :locale="activeLocale"
                            :post-type-slug="currentPostTypeSlug"
                        />
                    </div>

                    <AppMessage v-if="focusChecks.length" variant="info">
                        <p class="font-medium mb-1">{{ t("admin.posts.seo.focusChecksTitle") }}</p>
                        <ul class="space-y-0.5 text-xs">
                            <li v-for="check in focusChecks" :key="check.key" class="flex items-center gap-1.5">
                                <span v-if="check.ok" class="text-emerald-500">✓</span>
                                <span v-else class="text-rose-500">✗</span>
                                {{ t(`admin.posts.seo.focusChecks.${check.key}`) }}
                            </li>
                        </ul>
                    </AppMessage>
                </div>
            </div>

            <!-- JSON-LD -->
            <div class="border-t border-line pt-4 space-y-2">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="text-xs text-secondary uppercase tracking-wide">
                        {{ t("admin.posts.seo.jsonLd") }}
                    </label>
                    <AppButton variant="secondary" size="sm" v-on:click="generateArticleJsonLd">
                        {{ t("admin.posts.seo.generateArticle") }}
                    </AppButton>
                </div>
                <AppTextarea
                    v-model="jsonLdText"
                    :placeholder="t('admin.posts.seo.jsonLdPlaceholder')"
                    :rows="8"
                    :error="jsonLdError ?? ''"
                    mono
                />
            </div>
        </div>

        <!-- Editor.js -->
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
                <div class="w-full max-w-4xl bg-surface rounded-2xl border border-line shadow-2xl flex flex-col max-h-[85vh]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-line shrink-0">
                        <div>
                            <h2 class="text-base font-semibold text-primary">{{ t("admin.editor.templates.title") }}</h2>
                            <p class="text-xs text-muted mt-0.5">{{ t("admin.editor.templates.subtitle") }}</p>
                        </div>
                        <AppButton variant="ghost" size="none" class="p-1.5" v-on:click="closeTemplates">
                            <X class="w-5 h-5" :stroke-width="2" />
                        </AppButton>
                    </div>

                    <!-- Category filters -->
                    <div class="flex gap-2 px-6 py-3 border-b border-line shrink-0 flex-wrap">
                        <button
                            v-for="cat in ['all', ...TEMPLATE_CATEGORIES]"
                            :key="cat"
                            type="button"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                            :class="activeCategory === cat ? 'bg-indigo-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                            v-on:click="activeCategory = cat; confirmingTemplate = null"
                        >
                            {{ t("admin.editor.templates.categories." + cat) }}
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex flex-1 min-h-0" v-on:mouseleave="hoveredTemplate = null">
                        <!-- Grid -->
                        <div class="flex-1 overflow-y-auto scrollbar-thin p-6 grid grid-cols-1 sm:grid-cols-2 gap-3 content-start">
                            <button
                                v-for="template in filteredTemplates"
                                :key="template.id"
                                type="button"
                                class="relative text-left p-4 rounded-xl border transition-all group overflow-hidden"
                                :class="confirmingTemplate?.id === template.id
                                    ? 'border-indigo-500 bg-indigo-500/10'
                                    : 'border-line bg-surface-2 hover:border-indigo-500 hover:bg-indigo-500/5'"
                                v-on:click="confirmingTemplate = confirmingTemplate?.id === template.id ? null : template"
                                v-on:mouseenter="hoveredTemplate = template"
                            >
                                <!-- Confirm overlay -->
                                <Transition
                                    enter-active-class="transition ease-out duration-150"
                                    enter-from-class="opacity-0"
                                    enter-to-class="opacity-100"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <div v-if="confirmingTemplate?.id === template.id" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-surface/95 backdrop-blur-sm rounded-xl p-4">
                                        <p class="text-sm font-medium text-primary text-center">{{ t("admin.editor.templates.confirmReplace") }}</p>
                                        <div class="flex gap-2">
                                            <AppButton variant="primary" size="md" v-on:click.stop="applyTemplate(template)">{{ t("admin.editor.templates.apply") }}</AppButton>
                                            <AppButton variant="ghost" size="md" v-on:click.stop="confirmingTemplate = null">{{ t("shared.common.cancel") }}</AppButton>
                                        </div>
                                    </div>
                                </Transition>

                                <!-- Card content -->
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-2xl">{{ template.icon }}</span>
                                    <span class="font-medium text-primary text-sm group-hover:text-indigo-400 transition-colors">{{ t("admin.editor.templates." + template.id + ".label") }}</span>
                                </div>
                                <p class="text-xs text-muted">{{ t("admin.editor.templates." + template.id + ".description") }}</p>
                            </button>
                        </div>

                        <!-- Side preview panel -->
                        <div class="w-56 border-l border-line p-5 hidden md:flex flex-col gap-4 shrink-0 overflow-y-auto scrollbar-thin">
                            <Transition
                                enter-active-class="transition ease-out duration-150"
                                enter-from-class="opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                                mode="out-in"
                            >
                                <div v-if="hoveredTemplate" :key="hoveredTemplate.id" class="flex flex-col gap-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xl">{{ hoveredTemplate.icon }}</span>
                                            <span class="font-semibold text-sm text-primary">{{ t("admin.editor.templates." + hoveredTemplate.id + ".label") }}</span>
                                        </div>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-surface-3 text-secondary">
                                            {{ t("admin.editor.templates.categories." + hoveredTemplate.category) }}
                                        </span>
                                    </div>
                                    <div v-if="hoveredTemplate.blocks.length" class="flex flex-col gap-1.5">
                                        <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t("admin.editor.templates.structure") }}</p>
                                        <!-- header -->
                                        <template v-for="(block, i) in hoveredTemplate.blocks" :key="i">
                                            <div v-if="block.type === 'header'" class="h-2.5 rounded-sm bg-surface-3 w-3/4" />
                                            <div v-else-if="block.type === 'paragraph'" class="flex flex-col gap-1">
                                                <div class="h-1.5 rounded-sm bg-surface-3 w-full" />
                                                <div class="h-1.5 rounded-sm bg-surface-3 w-5/6" />
                                                <div class="h-1.5 rounded-sm bg-surface-3 w-4/6" />
                                            </div>
                                            <div v-else-if="block.type === 'image'" class="h-10 rounded-sm bg-surface-3 w-full flex items-center justify-center">
                                                <svg
                                                    class="w-4 h-4 text-muted/50"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="1.5"
                                                ><rect
                                                    x="3"
                                                    y="3"
                                                    width="18"
                                                    height="18"
                                                    rx="2"
                                                /><circle cx="8.5" cy="8.5" r="1.5" /><path d="m21 15-5-5L5 21" /></svg>
                                            </div>
                                            <div v-else-if="block.type === 'mediaText'" class="flex gap-1.5">
                                                <div class="h-8 rounded-sm bg-surface-3 w-2/5 shrink-0" />
                                                <div class="flex flex-col gap-1 flex-1 justify-center">
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-full" />
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-4/5" />
                                                </div>
                                            </div>
                                            <div v-else-if="block.type === 'twoColumn'" class="flex gap-1.5">
                                                <div class="flex flex-col gap-1 flex-1">
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-full" />
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-4/5" />
                                                </div>
                                                <div class="flex flex-col gap-1 flex-1">
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-full" />
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-3/5" />
                                                </div>
                                            </div>
                                            <div v-else-if="block.type === 'list'" class="flex flex-col gap-1">
                                                <div v-for="n in 3" :key="n" class="flex items-center gap-1">
                                                    <div class="w-1 h-1 rounded-full bg-surface-3 shrink-0" />
                                                    <div class="h-1.5 rounded-sm bg-surface-3" :class="n === 1 ? 'w-4/5' : n === 2 ? 'w-3/5' : 'w-2/3'" />
                                                </div>
                                            </div>
                                            <div v-else-if="block.type === 'code'" class="h-8 rounded-sm bg-surface-3 w-full px-2 flex flex-col justify-center gap-1">
                                                <div class="h-1 rounded-sm bg-muted/20 w-2/3" />
                                                <div class="h-1 rounded-sm bg-muted/20 w-1/2" />
                                            </div>
                                            <div v-else-if="block.type === 'callout'" class="h-7 rounded-sm bg-surface-3 w-full border-l-2 border-muted/30 pl-2 flex flex-col justify-center gap-1">
                                                <div class="h-1.5 rounded-sm bg-muted/30 w-3/4" />
                                                <div class="h-1 rounded-sm bg-muted/20 w-1/2" />
                                            </div>
                                            <div v-else-if="block.type === 'delimiter'" class="flex items-center gap-1.5 py-0.5">
                                                <div class="h-px flex-1 bg-surface-3" />
                                                <div class="w-1 h-1 rounded-full bg-surface-3" />
                                                <div class="h-px flex-1 bg-surface-3" />
                                            </div>
                                            <div v-else class="h-2 rounded-sm bg-surface-3 w-2/3" />
                                        </template>
                                    </div>
                                    <p v-else class="text-xs text-muted italic">{{ t("admin.editor.templates.emptyContent") }}</p>
                                    <p class="text-xs text-muted mt-auto pt-2 border-t border-line">
                                        {{ hoveredTemplate.blocks.length }}
                                        {{ hoveredTemplate.blocks.length > 1 ? t("admin.editor.templates.blocks") : t("admin.editor.templates.block") }}
                                    </p>
                                </div>
                                <p v-else key="empty" class="text-xs text-muted italic">{{ t("admin.editor.templates.subtitle") }}</p>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Preview overlay -->
    <PreviewOverlay
        :show="showPreview"
        :title="form.translations[activeLocale]?.title"
        :html="previewHtml"
        :featured-media-url="featuredMediaUrl"
        :label="t('admin.posts.preview') + ' - ' + t('shared.locales.' + activeLocale)"
        v-on:close="showPreview = false"
    />

    <!-- Remote version preview (conflict compare) -->
    <PostPreviewOverlay
        :post="remotePost"
        :loading="remoteLoading"
        :locales="locales"
        v-on:close="closeRemoteVersion"
    />

    <!-- Conflict merge overlay -->
    <ConflictMergeOverlay
        :show="showMerge"
        :base="baseTranslations"
        :local="form.translations"
        :remote="mergeRemoteTranslations ?? {}"
        :locales="locales"
        v-on:close="closeMerge"
        v-on:apply="applyMergeResolution"
    />

    <!-- Revisions overlay -->
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
