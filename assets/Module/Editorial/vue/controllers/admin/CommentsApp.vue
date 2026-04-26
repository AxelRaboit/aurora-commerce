<script setup>
import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref, computed, onMounted } from "vue";
import { usePaginatedFetch } from "@/shared/composables/usePaginatedFetch.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { MessageSquare, Check, Ban, Trash2, Eye } from "lucide-vue-next";
import AppPagination from "@/shared/components/AppPagination.vue";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppNoData from "@/shared/components/AppNoData.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppModalFooter from "@/shared/components/AppModalFooter.vue";
import AppBadge from "@/shared/components/AppBadge.vue";
import { truncate } from "@/shared/utils/truncate.js";
import { useDateFormat } from "@/shared/composables/useDateFormat.js";

const { t } = useI18n();
const { formatDateShort, formatDateTime } = useDateFormat();

const props = defineProps({
    listPath: { type: String, required: true },
    approvePath: { type: String, required: true },
    spamPath: { type: String, required: true },
    toggleModerationPath: { type: String, required: true },
    moderationEnabled: { type: Boolean, default: true },
    deletePath: { type: String, required: true },
    stats: { type: Object, default: () => ({ pending: 0, approved: 0, spam: 0 }) },
});

const statusFilter = ref("");
const localStats = ref({ ...props.stats });

const tabs = computed(() => [
    { key: "", label: t("admin.comments.all"), count: localStats.value.pending + localStats.value.approved + localStats.value.spam },
    { key: "pending", label: t("admin.comments.pending"), count: localStats.value.pending },
    { key: "approved", label: t("admin.comments.approved"), count: localStats.value.approved },
    { key: "spam", label: t("admin.comments.spam"), count: localStats.value.spam },
]);

const { items: comments, loading, page, totalPages, total, load: fetchComments, goToPage, reset: resetComments } = usePaginatedFetch(
    () => props.listPath,
    () => ({ ...(statusFilter.value && { status: statusFilter.value }) }),
);

onMounted(fetchComments);

function selectTab(key) {
    statusFilter.value = key;
    resetComments();
}

async function moderateComment(comment, path, successKey, statsUpdate) {
    try {
        const response = await fetch(path.replace("__id__", comment.id), { method: HttpMethod.Post });
        const data = await response.json();
        if (data.ok) {
            toast.success(t(successKey));
            statsUpdate(comment.status);
            fetchComments();
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    }
}

function approveComment(comment) {
    return moderateComment(comment, props.approvePath, "admin.comments.approveSuccess", (status) => {
        if (status === "pending") { localStats.value.pending = Math.max(0, localStats.value.pending - 1); localStats.value.approved += 1; }
        else if (status === "spam") { localStats.value.spam = Math.max(0, localStats.value.spam - 1); localStats.value.approved += 1; }
    });
}

function spamComment(comment) {
    return moderateComment(comment, props.spamPath, "admin.comments.spamSuccess", (status) => {
        if (status === "pending") { localStats.value.pending = Math.max(0, localStats.value.pending - 1); localStats.value.spam += 1; }
        else if (status === "approved") { localStats.value.approved = Math.max(0, localStats.value.approved - 1); localStats.value.spam += 1; }
    });
}

const pendingDelete = ref(null);
const deleteLoading = ref(false);

function confirmDelete(comment) {
    pendingDelete.value = comment;
}

async function doDelete() {
    if (!pendingDelete.value || deleteLoading.value) return;
    deleteLoading.value = true;
    const comment = pendingDelete.value;
    try {
        const response = await fetch(props.deletePath.replace("__id__", comment.id), { method: HttpMethod.Post });
        const data = await response.json();
        if (data.ok) {
            toast.success(t("shared.common.deleted"));
            if (comment.status === "pending") localStats.value.pending = Math.max(0, localStats.value.pending - 1);
            else if (comment.status === "approved") localStats.value.approved = Math.max(0, localStats.value.approved - 1);
            else if (comment.status === "spam") localStats.value.spam = Math.max(0, localStats.value.spam - 1);
            pendingDelete.value = null;
            fetchComments();
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        deleteLoading.value = false;
    }
}

function statusBadgeColor(status) {
    if (status === "approved") return "emerald";
    if (status === "spam") return "rose";
    return "amber";
}


const viewingComment = ref(null);
const isModerationEnabled = ref(props.moderationEnabled);

async function toggleModeration() {
    try {
        const response = await fetch(props.toggleModerationPath, { method: HttpMethod.Post });
        const data = await response.json();
        if (data.ok) {
            isModerationEnabled.value = data.moderationEnabled;
            toast.success(data.moderationEnabled ? t("admin.comments.moderationEnabled") : t("admin.comments.moderationDisabled"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    }
}
</script>

<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex gap-1 flex-wrap">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
                    :class="statusFilter === tab.key ? 'bg-accent-600/15 text-accent-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                    v-on:click="selectTab(tab.key)"
                >
                    {{ tab.label }}
                    <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full text-xs" :class="statusFilter === tab.key ? 'bg-accent-600/25' : 'bg-surface-3'">
                        {{ tab.count }}
                    </span>
                </button>
            </div>
            <AppButton
                :variant="isModerationEnabled ? 'primary' : 'secondary'"
                size="md"
                v-on:click="toggleModeration"
            >
                <span class="w-2 h-2 rounded-full" :class="isModerationEnabled ? 'bg-white' : 'bg-muted'" />
                {{ isModerationEnabled ? t("admin.comments.moderationOn") : t("admin.comments.moderationOff") }}
            </AppButton>
        </div>

        <!-- Mobile cards -->
        <div class="sm:hidden space-y-2">
            <AppNoData v-if="!loading && !comments.length" :message="t('admin.comments.empty')" />
            <div v-for="comment in comments" :key="comment.id" class="bg-surface border border-line/60 rounded-xl p-4 space-y-3 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-primary text-sm">
                            {{ comment.authorName }}
                            <span v-if="comment.replyCount > 0" class="ml-1.5 inline-flex items-center gap-0.5 text-xs text-secondary bg-surface-3 rounded px-1 py-0.5">↩ {{ comment.replyCount }}</span>
                        </p>
                        <p class="text-xs text-muted mt-0.5">{{ comment.authorEmail }}</p>
                        <p class="text-xs text-secondary mt-1.5 line-clamp-2">{{ comment.content }}</p>
                    </div>
                    <AppBadge :color="statusBadgeColor(comment.status)" class="shrink-0">{{ comment.statusLabel }}</AppBadge>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line/40">
                    <p class="text-xs text-muted">{{ formatDateShort(comment.createdAt) }}</p>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton color="accent" :title="t('admin.comments.view')" v-on:click="viewingComment = comment">
                            <Eye class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton v-if="comment.status !== 'approved'" color="emerald" :title="t('admin.comments.approve')" v-on:click="approveComment(comment)">
                            <Check class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton v-if="comment.status !== 'spam'" color="amber" :title="t('admin.comments.markSpam')" v-on:click="spamComment(comment)">
                            <Ban class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(comment)">
                            <Trash2 class="w-4 h-4" :stroke-width="2" />
                        </AppIconButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block bg-surface border border-line/60 rounded-xl overflow-hidden">
            <AppNoData v-if="!loading && !comments.length" :message="t('admin.comments.empty')" />
            <table v-else class="w-full text-sm">
                <thead class="bg-surface-2 text-xs text-secondary uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.comments.name') }}</th>
                        <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">{{ t('admin.comments.email') }}</th>
                        <th class="text-left px-4 py-3 font-semibold hidden lg:table-cell">{{ t('admin.comments.post') }}</th>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.comments.content') }}</th>
                        <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">{{ t('admin.comments.date') }}</th>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.comments.status') }}</th>
                        <th class="text-right px-4 py-3 font-semibold">{{ t('shared.common.edit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="comment in comments" :key="comment.id" class="border-t border-line/60 hover:bg-surface-2/50">
                        <td class="px-4 py-3 text-primary font-medium whitespace-nowrap">
                            {{ comment.authorName }}
                            <span v-if="comment.replyCount > 0" class="ml-1.5 inline-flex items-center gap-0.5 text-xs text-secondary bg-surface-3 rounded px-1 py-0.5">↩ {{ comment.replyCount }}</span>
                        </td>
                        <td class="px-4 py-3 text-secondary text-xs hidden md:table-cell">{{ comment.authorEmail }}</td>
                        <td class="px-4 py-3 text-secondary text-xs hidden lg:table-cell">{{ truncate(comment.postTitle, 40) }}</td>
                        <td class="px-4 py-3 text-secondary max-w-xs">{{ truncate(comment.content, 100) }}</td>
                        <td class="px-4 py-3 text-xs text-muted whitespace-nowrap hidden md:table-cell">{{ formatDateShort(comment.createdAt) }}</td>
                        <td class="px-4 py-3">
                            <AppBadge :color="statusBadgeColor(comment.status)">{{ comment.statusLabel }}</AppBadge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="accent" :title="t('admin.comments.view')" v-on:click="viewingComment = comment">
                                    <Eye class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="comment.status !== 'approved'" color="emerald" :title="t('admin.comments.approve')" v-on:click="approveComment(comment)">
                                    <Check class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="comment.status !== 'spam'" color="amber" :title="t('admin.comments.markSpam')" v-on:click="spamComment(comment)">
                                    <Ban class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(comment)">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

        <AppModal :show="!!viewingComment" max-width="md" v-on:close="viewingComment = null">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.comments.view') }}</h3>
            <div class="space-y-4">
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('admin.comments.name') }}</label>
                    <p class="text-sm text-primary font-medium">{{ viewingComment?.authorName }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('admin.comments.email') }}</label>
                    <p class="text-sm text-secondary">{{ viewingComment?.authorEmail }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('admin.comments.post') }}</label>
                    <p class="text-sm text-secondary">{{ viewingComment?.postTitle }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('admin.comments.date') }}</label>
                    <p class="text-sm text-muted">{{ viewingComment ? formatDateTime(viewingComment.createdAt) : '' }}</p>
                </div>
                <div v-if="viewingComment?.parentId" class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('admin.comments.replyTo') }}</label>
                    <p class="text-sm text-secondary">{{ viewingComment?.parentAuthorName }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('admin.comments.content') }}</label>
                    <p class="text-sm text-primary whitespace-pre-wrap bg-surface-2 rounded-lg px-3 py-2.5">{{ viewingComment?.content }}</p>
                </div>
                <div v-if="viewingComment?.reactionCount > 0" class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('admin.comments.reactions') }}</label>
                    <p class="text-sm text-secondary">{{ viewingComment?.reactionCount }}</p>
                </div>
            </div>
            <AppModalFooter bordered>
                <AppIconButton v-if="viewingComment?.status !== 'approved'" color="emerald" :title="t('admin.comments.approve')" v-on:click="approveComment(viewingComment); viewingComment = null">
                    <Check class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton v-if="viewingComment?.status !== 'spam'" color="amber" :title="t('admin.comments.markSpam')" v-on:click="spamComment(viewingComment); viewingComment = null">
                    <Ban class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(viewingComment); viewingComment = null">
                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                </AppIconButton>
                <AppIconButton color="default" :title="t('shared.common.cancel')" v-on:click="viewingComment = null">
                    <span class="text-xs px-1">✕</span>
                </AppIconButton>
            </AppModalFooter>
        </AppModal>

        <!-- Delete confirm -->
        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t('admin.comments.deleteConfirm') }}</p>
            <p class="text-sm text-secondary">{{ t('admin.comments.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
