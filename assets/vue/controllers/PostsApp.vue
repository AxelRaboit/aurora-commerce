<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import AppNoData from "@/components/AppNoData.vue";
import AppModal from "@/components/AppModal.vue";
import { useDateFormat } from "@/composables/useDateFormat.js";
import { statusBadge } from "@/utils/statusStyles.js";
import { DEFAULT_LOCALES } from "@/utils/lang.js";
import { usePostList } from "@/admin/posts/composables/usePostList.js";
import { usePostDelete } from "@/admin/posts/composables/usePostDelete.js";
import { Pencil, Trash2, Plus, FileText, Search, Eye, Inbox, RotateCcw } from "lucide-vue-next";
import PostEditor from "@/admin/posts/PostEditor.vue";
import PostPreviewOverlay from "@/admin/posts/PostPreviewOverlay.vue";
import AppButton from "@/components/AppButton.vue";
import AppIconButton from "@/components/AppIconButton.vue";
import AppPagination from "@/components/AppPagination.vue";

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
    editPath: { type: String, required: true },
    deletePath: { type: String, required: true },
    restorePath: { type: String, required: true },
    forceDeletePath: { type: String, required: true },
});

function setTrashedFilter(trashed) {
    const url = new URL(props.postsPath, window.location.origin);
    if (trashed) url.searchParams.set("trashed", "1");
    window.location.href = url.toString();
}

async function restorePost(post) {
    try {
        const response = await fetch(props.restorePath.replace("__id__", post.id), { method: "POST" });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success) {
            removePost(post.id);
            toast.success(t("admin.posts.restored"));
        }
    } catch {
        toast.error(t("common.error"));
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
    usePostList(props.postsPath, props.posts, props.search);

const deletePost = usePostDelete(
    props.trashed ? props.forceDeletePath : props.deletePath,
    (id) => removePost(id),
    props.trashed ? "admin.posts.deletedForever" : "admin.posts.deleted",
);

const previewPost = ref(null);
const previewLoading = ref(false);

async function openPreview(post) {
    previewLoading.value = true;
    previewPost.value = null;
    try {
        const response = await fetch(props.showPath.replace("__id__", post.id));
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success) previewPost.value = data.post;
    } catch {
        toast.error(t("common.error"));
    } finally {
        previewLoading.value = false;
    }
}
</script>

<template>
    <!-- Editor view -->
    <PostEditor
        v-if="view === 'editor'"
        :post-id="editingPostId"
        :post-types="parsedPostTypes"
        :taxonomies="parsedTaxonomies"
        :locales="parsedLocales"
        :show-path="showPath"
        :create-path="createPath"
        :edit-path="editPath"
        v-on:saved="onEditorSaved"
        v-on:back="view = 'list'"
    />

    <!-- List view -->
    <div v-else class="space-y-4">
        <!-- Tabs: active vs trash -->
        <div class="flex border-b border-line/40 text-sm">
            <button
                type="button"
                class="px-4 py-2 border-b-2 font-medium transition-colors"
                :class="!trashed ? 'border-indigo-500 text-primary' : 'border-transparent text-muted hover:text-secondary'"
                v-on:click="setTrashedFilter(false)"
            >
                <FileText class="inline w-4 h-4 mr-1.5 -mt-0.5" :stroke-width="2" />
                {{ t("admin.posts.tabs.active") }}
            </button>
            <button
                type="button"
                class="px-4 py-2 border-b-2 font-medium transition-colors"
                :class="trashed ? 'border-rose-500 text-primary' : 'border-transparent text-muted hover:text-secondary'"
                v-on:click="setTrashedFilter(true)"
            >
                <Inbox class="inline w-4 h-4 mr-1.5 -mt-0.5" :stroke-width="2" />
                {{ t("admin.posts.tabs.trash") }}
            </button>
        </div>

        <!-- Toolbar -->
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted pointer-events-none" :stroke-width="2" />
                <input
                    v-model="searchInput"
                    type="text"
                    :placeholder="t('admin.posts.searchPlaceholder')"
                    class="w-full pl-9 pr-4 py-2 rounded-lg bg-surface-2 border border-line/60 text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                    v-on:keyup.enter="performSearch"
                >
            </div>
            <AppButton variant="secondary" size="md" class="w-full sm:w-auto" v-on:click="performSearch">
                <Search class="w-4 h-4" :stroke-width="2" />
                {{ t("admin.users.search") }}
            </AppButton>
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
        </div>

        <!-- Mobile cards -->
        <div class="sm:hidden space-y-2">
            <AppNoData v-if="!posts.length" :message="t('admin.posts.empty')" />
            <div v-for="post in posts" :key="post.id" class="bg-surface border border-line/60 rounded-xl p-4 space-y-3 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-primary truncate text-sm">{{ post.title ?? "-" }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ post.postType?.label }}</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium shrink-0" :class="statusBadge(post.status)">
                        {{ t("admin.stats.postStatus." + post.status) }}
                    </span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line/40">
                    <p class="text-xs text-muted">{{ formatDateShort(post.createdAt) }}</p>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton color="sky" v-on:click="openPreview(post)">
                            <Eye class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton v-if="!trashed" color="indigo" v-on:click="openEdit(post)">
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

        <!-- Desktop table -->
        <div class="hidden sm:block bg-surface border border-line/60 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("admin.posts.title") }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t("admin.posts.postType") }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("admin.posts.status") }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t("admin.tags.createdAt") }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("admin.tags.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-if="!posts.length">
                        <td colspan="5"><AppNoData :message="t('admin.posts.empty')" /></td>
                    </tr>
                    <tr v-for="post in posts" :key="post.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <FileText class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="2" />
                                <span class="font-medium text-primary text-sm">{{ post.title ?? "-" }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-secondary hidden md:table-cell">{{ post.postType?.label ?? "-" }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="statusBadge(post.status)">
                                {{ t("admin.stats.postStatus." + post.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(post.createdAt) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="sky" v-on:click="openPreview(post)">
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="!trashed" color="indigo" v-on:click="openEdit(post)">
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

        <!-- Pagination -->
        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

        <!-- Delete confirmation modal -->
        <AppModal :show="!!deletePost.pendingDelete.value" max-width="sm" v-on:close="deletePost.pendingDelete.value = null">
            <p class="text-sm text-primary">
                {{ t(trashed ? "admin.posts.forceDeleteConfirm" : "admin.posts.deleteConfirm", { title: deletePost.pendingDelete.value?.title ?? "?" }) }}
            </p>
            <div class="flex justify-end gap-2 mt-2">
                <AppButton variant="ghost" size="md" v-on:click="deletePost.pendingDelete.value = null">
                    {{ t("common.cancel") }}
                </AppButton>
                <AppButton variant="danger" size="md" :loading="deletePost.loading.value" v-on:click="deletePost.submit()">
                    {{ t(trashed ? "admin.posts.forceDelete" : "common.delete") }}
                </AppButton>
            </div>
        </AppModal>
    </div>

    <PostPreviewOverlay
        :post="previewPost"
        :loading="previewLoading"
        :locales="parsedLocales"
        v-on:close="previewPost = null"
    />
</template>
