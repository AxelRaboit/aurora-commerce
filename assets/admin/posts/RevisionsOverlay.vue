<script setup>
import { HttpMethod } from "@/utils/httpMethod.js";
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { X, RotateCcw, History as HistoryIcon } from "lucide-vue-next";
import { renderBlocks } from "@/utils/blocksRenderer.js";
import { diffBlocksAgainstRevision, summarizeRevisionDiff, RevisionDiffKind } from "@/utils/revisionDiff.js";
import { statusBadgeColor } from "@/utils/statusStyles.js";
import AppButton from "@/components/AppButton.vue";
import AppIconButton from "@/components/AppIconButton.vue";
import AppCheckbox from "@/components/AppCheckbox.vue";
import AppBadge from "@/components/AppBadge.vue";
import { toast } from "vue-sonner";
import { useDateFormat } from "@/composables/useDateFormat.js";

const { t } = useI18n();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    postId: { type: Number, required: true },
    show: { type: Boolean, default: false },
    locales: { type: Array, default: () => [] },
    currentTranslations: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["close", "restored"]);

const revisions = ref([]);
const loadingList = ref(false);
const selectedRevision = ref(null);
const loadingSelected = ref(false);
const restoring = ref(false);
const activeLocale = ref(props.locales[0] ?? "fr");

watch(
    () => props.show,
    async (visible) => {
        if (!visible) return;
        activeLocale.value = props.locales[0] ?? "fr";
        selectedRevision.value = null;
        await fetchRevisions();
    },
);

async function fetchRevisions() {
    loadingList.value = true;
    try {
        const response = await fetch(`/admin/posts/${props.postId}/revisions`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        revisions.value = data.revisions ?? [];
    } catch {
        toast.error(t("common.error"));
    } finally {
        loadingList.value = false;
    }
}

async function selectRevision(revision) {
    selectedRevision.value = null;
    loadingSelected.value = true;
    try {
        const response = await fetch(`/admin/posts/${props.postId}/revisions/${revision.id}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        selectedRevision.value = data.revision ?? null;
    } catch {
        toast.error(t("common.error"));
    } finally {
        loadingSelected.value = false;
    }
}

async function restore() {
    if (!selectedRevision.value) return;
    restoring.value = true;
    try {
        const response = await fetch(`/admin/posts/${props.postId}/revisions/${selectedRevision.value.id}/restore`, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success) {
            toast.success(t("admin.posts.revisions.restored"));
            emit("restored");
        } else {
            toast.error(t("common.error"));
        }
    } catch {
        toast.error(t("common.error"));
    } finally {
        restoring.value = false;
    }
}

const revisionTranslations = computed(() => selectedRevision.value?.snapshot?.translations ?? {});

const diffEntries = computed(() => {
    const locale = activeLocale.value;
    const currentBlocks = props.currentTranslations?.[locale]?.blocks ?? [];
    const revisionBlocks = revisionTranslations.value?.[locale]?.blocks ?? [];
    return diffBlocksAgainstRevision(currentBlocks, revisionBlocks);
});

const stats = computed(() => summarizeRevisionDiff(diffEntries.value));

const KIND_CLASS = {
    [RevisionDiffKind.Unchanged]: "bg-surface-2 text-muted",
    [RevisionDiffKind.Added]: "bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300",
    [RevisionDiffKind.Removed]: "bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300",
    [RevisionDiffKind.Modified]: "bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300",
};

function kindLabel(kind) {
    return t(`admin.posts.revisions.kind.${kind}`);
}

function renderBlock(block) {
    if (!block) return "";
    return renderBlocks([block]);
}

function formatDate(iso) {
    if (!iso) return "";
    try {
        return formatDateTime(iso);
    } catch {
        return iso;
    }
}

const showUnchanged = ref(false);
const visibleEntries = computed(() =>
    showUnchanged.value
        ? diffEntries.value
        : diffEntries.value.filter((entry) => entry.kind !== RevisionDiffKind.Unchanged),
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
            <div v-if="show" class="fixed inset-0 z-50 flex flex-col bg-bg overflow-hidden">
                <!-- Header -->
                <div class="flex items-center gap-3 px-6 py-3 border-b border-line bg-surface/90 backdrop-blur-sm shrink-0">
                    <HistoryIcon class="w-4 h-4 text-secondary" :stroke-width="2" />
                    <span class="flex-1 text-sm font-medium text-secondary truncate">
                        {{ t("admin.posts.revisions.title") }}
                    </span>
                    <AppIconButton :title="t('common.close')" v-on:click="emit('close')">
                        <X class="w-5 h-5" :stroke-width="2" />
                    </AppIconButton>
                </div>

                <!-- Body: two columns -->
                <div class="flex-1 flex min-h-0">
                    <!-- Revisions list -->
                    <aside class="w-80 shrink-0 border-r border-line bg-surface overflow-y-auto scrollbar-thin">
                        <div v-if="loadingList" class="p-4 text-sm text-muted">{{ t("common.loading") }}</div>
                        <div v-else-if="revisions.length === 0" class="p-4 text-sm text-muted">
                            {{ t("admin.posts.revisions.empty") }}
                        </div>
                        <ul v-else class="divide-y divide-line">
                            <li
                                v-for="revision in revisions"
                                :key="revision.id"
                                class="px-4 py-3 cursor-pointer transition-colors"
                                :class="selectedRevision?.id === revision.id ? 'bg-surface-2' : 'hover:bg-surface-2/50'"
                                v-on:click="selectRevision(revision)"
                            >
                                <div class="flex items-center gap-2 text-xs">
                                    <AppBadge :color="statusBadgeColor(revision.status)">
                                        {{ t("admin.stats.postStatus." + revision.status) }}
                                    </AppBadge>
                                    <span class="text-muted font-mono">v{{ revision.postVersion }}</span>
                                </div>
                                <div class="mt-1 text-sm text-primary">{{ formatDate(revision.createdAt) }}</div>
                                <div v-if="revision.author" class="text-xs text-muted truncate">
                                    {{ revision.author.email }}
                                </div>
                            </li>
                        </ul>
                    </aside>

                    <!-- Diff viewer -->
                    <section class="flex-1 overflow-y-auto scrollbar-thin">
                        <div v-if="!selectedRevision && !loadingSelected" class="p-8 text-sm text-muted text-center">
                            {{ t("admin.posts.revisions.selectHint") }}
                        </div>
                        <div v-else-if="loadingSelected" class="p-8 text-sm text-muted text-center">
                            {{ t("common.loading") }}
                        </div>
                        <div v-else class="p-6 space-y-4">
                            <!-- Locale tabs + stats + restore -->
                            <div class="flex flex-wrap items-center gap-3">
                                <div v-if="locales.length > 1" class="flex gap-1">
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
                                        {{ locale.toUpperCase() }}
                                    </button>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ stats.added }}</span>
                                    <span class="text-amber-600 dark:text-amber-400">~{{ stats.modified }}</span>
                                    <span class="text-rose-600 dark:text-rose-400">-{{ stats.removed }}</span>
                                    <span class="text-muted">· {{ stats.unchanged }} {{ t("admin.posts.revisions.unchanged") }}</span>
                                </div>
                                <AppCheckbox
                                    v-model="showUnchanged"
                                    :label="t('admin.posts.revisions.showUnchanged')"
                                    class="text-xs ml-auto"
                                />
                                <AppButton variant="primary" size="sm" :loading="restoring" v-on:click="restore">
                                    <RotateCcw class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t("admin.posts.revisions.restore") }}
                                </AppButton>
                            </div>

                            <!-- Block entries -->
                            <div class="space-y-3">
                                <div v-if="visibleEntries.length === 0" class="p-4 text-sm text-muted text-center border border-line rounded-lg">
                                    {{ t("admin.posts.revisions.noChange") }}
                                </div>
                                <div
                                    v-for="entry in visibleEntries"
                                    :key="entry.id"
                                    class="border border-line rounded-lg overflow-hidden"
                                >
                                    <div class="flex items-center gap-2 px-3 py-1.5 text-xs bg-surface-2 border-b border-line">
                                        <span class="px-2 py-0.5 rounded-md font-medium" :class="KIND_CLASS[entry.kind]">{{ kindLabel(entry.kind) }}</span>
                                        <span class="text-muted font-mono">#{{ entry.id.slice(0, 8) }}</span>
                                    </div>

                                    <!-- Modified: side-by-side -->
                                    <div v-if="entry.kind === RevisionDiffKind.Modified" class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-line">
                                        <div class="p-3">
                                            <div class="text-xs text-rose-600 dark:text-rose-400 mb-1.5">{{ t("admin.posts.revisions.revisionSide") }}</div>
                                            <div class="prose-preview text-sm" v-html="renderBlock(entry.revision)" />
                                        </div>
                                        <div class="p-3">
                                            <div class="text-xs text-emerald-600 dark:text-emerald-400 mb-1.5">{{ t("admin.posts.revisions.currentSide") }}</div>
                                            <div class="prose-preview text-sm" v-html="renderBlock(entry.current)" />
                                        </div>
                                    </div>

                                    <!-- Added/Removed/Unchanged: single block -->
                                    <div v-else class="p-3 prose-preview text-sm">
                                        <div v-html="renderBlock(entry.current ?? entry.revision)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
