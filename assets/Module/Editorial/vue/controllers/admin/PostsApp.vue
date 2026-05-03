<script setup>
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { ref } from "vue";
import { useUrlSyncedState } from "@/shared/composables/list/useUrlSyncedState.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { statusBadgeColor } from "@/shared/utils/format/statusStyles.js";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import { DEFAULT_LOCALES } from "@/shared/utils/lang.js";
import { usePostList } from "@editorial/admin/posts/composables/usePostList.js";
import { usePostDelete } from "@editorial/admin/posts/composables/usePostDelete.js";
import { Pencil, Trash2, Plus, FileText, Eye, Inbox, RotateCcw } from "lucide-vue-next";
import PostEditor from "@editorial/admin/posts/PostEditor.vue";
import PostPreviewOverlay from "@editorial/admin/posts/PostPreviewOverlay.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import { useUrlSearchSync } from "@/shared/composables/list/useUrlSearchSync.js";
import { PostStatus } from "@editorial/utils/enums/postStatus.js";

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const props = defineProps({
    postsPath: { type: String, required: true },
    posts: { type: Object, default: () => ({ items: [], total: 0, page: 1, totalPages: 1 }) },
    search: { type: String, default: "" },
    postTypes: { type: Array, default: () => [] },
    taxonomies: { type: Array, default: () => [] },
    locales: { type: Array, default: () => DEFAULT_LOCALES },
    trashed: { type: Boolean, default: false },
    createPath: { type: String, required: true },
    showPath: { type: String, required: true },
    previewPath: { type: String, required: true },
    editPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    restorePath: { type: String, required: true },
    forceDeletePath: { type: String, required: true },
    emptyTrashPath: { type: String, default: "" },
});

const emptyingTrash = ref(false);
const confirmEmptyTrash = ref(false);

async function emptyTrash() {
    if (!props.emptyTrashPath) return;
    emptyingTrash.value = true;
    try {
        const response = await fetch(props.emptyTrashPath, { method: HttpMethod.Post });
        const data = await response.json();
        if (data.success) {
            toast.success(t("admin.posts.emptyTrashDone", { count: data.count }));
            setTrashedFilter(true);
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        emptyingTrash.value = false;
        confirmEmptyTrash.value = false;
    }
}

// Reactive trash filter — kept in sync with the URL so switching tabs is
// instant (no full page reload) while Back/Forward still work.
const { state: trashed, set: setTrashedFilter } = useUrlSyncedState({
    initial: props.trashed,
    serialize: (value) => {
        const url = new URL(window.location.href);
        if (value) url.searchParams.set("trashed", "1");
        else url.searchParams.delete("trashed");
        return url;
    },
    deserialize: (event) => event.state?.value
        ?? (new URLSearchParams(window.location.search).get("trashed") === "1"),
    onSync: () => performSearch(),
});

async function restorePost(post) {
    try {
        const response = await fetch(buildPath(props.restorePath, { id: post.id }), { method: HttpMethod.Post });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success) {
            removePost(post.id);
            toast.success(t("admin.posts.restored"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    }
}

const parsedPostTypes = props.postTypes ?? [];
const parsedTaxonomies = props.taxonomies ?? [];
const parsedLocales   = props.locales ?? DEFAULT_LOCALES;

// View state: 'list' | 'editor'
const view = ref("list");
const editingPostId = ref(null);

function openCreate() {
    editingPostId.value = null;
    view.value = "editor";
}

function openEdit(post) {
    editingPostId.value = post.id;
    view.value = "editor";
}

function onEditorSaved(post, isNew) {
    if (isNew) {
        addPost(post);
        editingPostId.value = post.id;
    } else {
        updatePost(post);
    }
}

const { posts, page, totalPages, search: searchInput, addPost, updatePost, removePost, performSearch, goToPage } =
    usePostList(props.postsPath, props.posts, props.search, () => (trashed.value ? { trashed: "1" } : {}));

const syncSearchUrl = useUrlSearchSync();
function onSearch(value) {
    syncSearchUrl(value);
    performSearch();
}

const deletePost = usePostDelete(
    () => (trashed.value ? props.forceDeletePath : props.deletePath),
    (id) => removePost(id),
    () => (trashed.value ? "admin.posts.deletedForever" : "admin.posts.deleted"),
);

const previewPost = ref(null);
const previewLoading = ref(false);

function frontUrl(post) {
    const locale = props.locales[0] ?? "fr";
    if (!post.slug || !post.postType?.slug) return null;
    return `/${locale}/${post.postType.slug}/${post.slug}`;
}

async function openPreview(post) {
    previewLoading.value = true;
    previewPost.value = null;
    try {
        const response = await fetch(buildPath(props.showPath, { id: post.id }));
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success) previewPost.value = data.post;
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        previewLoading.value = false;
    }
}
</script>

<template>
    <PostEditor
        v-if="view === 'editor'"
        :post-id="editingPostId"
        :post-types="parsedPostTypes"
        :taxonomies="parsedTaxonomies"
        :locales="parsedLocales"
        :show-path="showPath"
        :create-path="createPath"
        :edit-path="editPath"
        :preview-path="previewPath"
        v-on:saved="onEditorSaved"
        v-on:back="view = 'list'"
    />

    <div v-else class="flex flex-col md:flex-row gap-6">
        <nav class="hidden md:flex flex-col w-44 shrink-0 gap-0.5">
            <AppTab :active="!trashed" v-on:click="setTrashedFilter(false)">
                <FileText class="w-4 h-4 shrink-0" :stroke-width="2" />
                {{ t("admin.posts.tabs.active") }}
            </AppTab>
            <AppTab :active="trashed" color="rose" v-on:click="setTrashedFilter(true)">
                <Inbox class="w-4 h-4 shrink-0" :stroke-width="2" />
                {{ t("admin.posts.tabs.trash") }}
            </AppTab>
        </nav>

        <div class="flex md:hidden gap-1 flex-wrap w-full">
            <AppTab :active="!trashed" size="sm" v-on:click="setTrashedFilter(false)">
                <FileText class="w-4 h-4" :stroke-width="2" />
                {{ t("admin.posts.tabs.active") }}
            </AppTab>
            <AppTab :active="trashed" color="rose" size="sm" v-on:click="setTrashedFilter(true)">
                <Inbox class="w-4 h-4" :stroke-width="2" />
                {{ t("admin.posts.tabs.trash") }}
            </AppTab>
        </div>

        <div class="flex-1 min-w-0 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
                <AppSearchInput
                    v-model="searchInput"
                    :placeholder="t('admin.posts.searchPlaceholder')"
                    v-on:search="onSearch"
                />
                <AppButton
                    v-if="!trashed"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openCreate"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t("admin.posts.add") }}
                </AppButton>
                <AppButton
                    v-if="trashed && posts.length"
                    variant="danger"
                    size="md"
                    class="w-full sm:w-auto"
                    :loading="emptyingTrash"
                    v-on:click="confirmEmptyTrash = true"
                >
                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                    {{ t("admin.posts.emptyTrash") }}
                </AppButton>
            </div>

            <div class="sm:hidden space-y-2">
                <AppNoData v-if="!posts.length" :message="t('admin.posts.empty')" />
                <div v-for="post in posts" :key="post.id" class="bg-surface border border-line/60 rounded-xl p-4 space-y-3 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-primary truncate text-sm">{{ post.title ?? "-" }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ post.postType?.label }}</p>
                            <p v-if="frontUrl(post) && !trashed" class="text-xs text-accent-400 truncate mt-0.5 font-mono">{{ frontUrl(post) }}</p>
                        </div>
                        <AppBadge :color="post.trashed ? 'rose' : statusBadgeColor(post.status)" class="shrink-0">
                            {{ post.trashed ? t("admin.posts.statusTrashed") : t("admin.stats.postStatus." + post.status) }}
                        </AppBadge>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-line/40">
                        <p class="text-xs text-muted">{{ formatDateShort(post.createdAt) }}</p>
                        <div class="flex items-center gap-0.5">
                            <AppIconButton color="sky" v-on:click="openPreview(post)">
                                <Eye class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton v-if="!trashed" color="accent" v-on:click="openEdit(post)">
                                <Pencil class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton v-if="trashed" color="emerald" v-on:click="restorePost(post)">
                                <RotateCcw class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton color="rose" v-on:click="deletePost.confirm(post)">
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("admin.posts.title") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("admin.posts.postType") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("admin.posts.status") }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("admin.tags.createdAt") }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("admin.tags.actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-if="!posts.length">
                            <td colspan="6"><AppNoData :message="t('admin.posts.empty')" /></td>
                        </tr>
                        <tr v-for="post in posts" :key="post.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3 text-xs text-muted font-mono hidden lg:table-cell">{{ post.id }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2.5">
                                    <FileText class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" />
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-primary text-sm truncate">{{ post.title ?? "-" }}</p>
                                        <p v-if="frontUrl(post) && !trashed" class="text-xs text-accent-400 truncate font-mono">{{ frontUrl(post) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-sm text-secondary hidden md:table-cell">{{ post.postType?.label ?? "-" }}</td>
                            <td class="px-6 py-3">
                                <AppBadge :color="post.trashed ? 'rose' : statusBadgeColor(post.status)">
                                    {{ post.trashed ? t("admin.posts.statusTrashed") : t("admin.stats.postStatus." + post.status) }}
                                </AppBadge>
                            </td>
                            <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(post.createdAt) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton color="sky" v-on:click="openPreview(post)">
                                        <Eye class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="!trashed" color="accent" v-on:click="openEdit(post)">
                                        <Pencil class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="trashed" color="emerald" v-on:click="restorePost(post)">
                                        <RotateCcw class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton color="rose" v-on:click="deletePost.confirm(post)">
                                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

            <AppModal :show="!!deletePost.pendingDelete.value" max-width="sm" v-on:close="deletePost.pendingDelete.value = null">
                <p class="text-sm text-primary">
                    {{ t(trashed ? "admin.posts.forceDeleteConfirm" : "admin.posts.deleteConfirm", { title: deletePost.pendingDelete.value?.title ?? "?" }) }}
                </p>
                <div class="flex justify-end gap-2 mt-2">
                    <AppButton variant="ghost" size="md" v-on:click="deletePost.pendingDelete.value = null">
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deletePost.loading.value" v-on:click="deletePost.submit()">
                        {{ t(trashed ? "admin.posts.forceDelete" : "common.delete") }}
                    </AppButton>
                </div>
            </AppModal>

            <AppModal :show="confirmEmptyTrash" max-width="sm" v-on:close="confirmEmptyTrash = false">
                <div class="space-y-4">
                    <p class="text-sm text-primary">{{ t("admin.posts.emptyTrashConfirm") }}</p>
                    <div class="flex justify-end gap-2">
                        <AppButton variant="secondary" size="md" v-on:click="confirmEmptyTrash = false">
                            {{ t("shared.common.cancel") }}
                        </AppButton>
                        <AppButton variant="danger" size="md" :loading="emptyingTrash" v-on:click="emptyTrash">
                            {{ t("admin.posts.emptyTrash") }}
                        </AppButton>
                    </div>
                </div>
            </AppModal>
        </div>

        <PostPreviewOverlay
            :post="previewPost"
            :loading="previewLoading"
            :locales="parsedLocales"
            :preview-path="previewPath"
            v-on:close="previewPost = null"
        />
    </div>
</template>
