import { ref, watch } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";

/**
 * Bundle of inline task-edit sub-features (comments, checklist, time entries,
 * attachments) — all bound to the same `editingTask` ref. State is local to
 * the composable; each action persists immediately and reloads the detail.
 */
export function useTaskExtras(paths, editingTask, reloadDetail) {
    const { t } = useI18n();

    const {
        errors: commentErrors,
        validate: validateComment,
        clearErrors: clearCommentErrors,
    } = useForm();
    const {
        errors: itemErrors,
        validate: validateItem,
        clearErrors: clearItemErrors,
    } = useForm();
    const {
        errors: timeErrors,
        validate: validateTime,
        clearErrors: clearTimeErrors,
    } = useForm();

    // ── Comments ─────────────────────────────────────────────────────────────
    const newCommentContent = ref("");
    const commentLoading = ref(false);
    const timeLoading = ref(false);

    async function submitComment() {
        if (!editingTask.value) return;
        if (
            !validateComment({
                content: () =>
                    required(t("backend.projects.errors.comment_required"))(
                        newCommentContent.value,
                    ),
            })
        )
            return;
        const content = newCommentContent.value.trim();
        const url = buildPath(paths.commentCreate, {
            taskId: editingTask.value.id,
        });
        commentLoading.value = true;
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ content }),
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (!data.success) throw new Error();
            newCommentContent.value = "";
            clearCommentErrors();
            await reloadDetail();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            commentLoading.value = false;
        }
    }

    async function deleteComment(comment) {
        const url = buildPath(paths.commentDelete, { commentId: comment.id });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
            });
            if (!response.ok) throw new Error();
            await reloadDetail();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    // ── Checklist ────────────────────────────────────────────────────────────
    const localItems = ref([]);
    const newItemLabel = ref("");

    watch(
        () => editingTask.value?.id,
        () => {
            localItems.value = (editingTask.value?.items ?? []).map((item) => ({
                ...item,
            }));
        },
        { immediate: true },
    );

    async function persistItems() {
        if (!editingTask.value) return;
        const payload = localItems.value.map((item) => ({
            label: item.label,
            done: item.done,
        }));
        const url = buildPath(paths.itemsReplace, {
            taskId: editingTask.value.id,
        });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ items: payload }),
            });
            if (!response.ok) throw new Error();
            await reloadDetail();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    function addItem() {
        if (
            !validateItem({
                label: () =>
                    required(t("backend.projects.errors.item_label_required"))(
                        newItemLabel.value,
                    ),
            })
        )
            return;
        const label = newItemLabel.value.trim();
        localItems.value.push({
            id: null,
            label,
            done: false,
            position: localItems.value.length,
        });
        newItemLabel.value = "";
        clearItemErrors();
        persistItems();
    }

    function toggleItem(item) {
        item.done = !item.done;
        persistItems();
    }

    function removeItem(index) {
        localItems.value.splice(index, 1);
        persistItems();
    }

    // ── Time entries ─────────────────────────────────────────────────────────
    const newTimeEntry = ref({
        minutes: "",
        note: "",
        loggedAt: new Date().toISOString().slice(0, 10),
    });

    async function logTime() {
        if (!editingTask.value) return;
        if (
            !validateTime({
                minutes: () => {
                    const minutes = Number(newTimeEntry.value.minutes);
                    return !minutes || minutes <= 0
                        ? t("backend.projects.errors.time_minutes_invalid")
                        : null;
                },
            })
        )
            return;
        const url = buildPath(paths.timeEntryCreate, {
            taskId: editingTask.value.id,
        });
        timeLoading.value = true;
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(newTimeEntry.value),
            });
            if (!response.ok) throw new Error();
            const data = await response.json();
            if (!data.success) {
                if (data.errors?.minutes) toast.error(t(data.errors.minutes));
                else toast.error(t("shared.common.error"));
                return;
            }
            newTimeEntry.value = {
                minutes: "",
                note: "",
                loggedAt: new Date().toISOString().slice(0, 10),
            };
            clearTimeErrors();
            await reloadDetail();
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            timeLoading.value = false;
        }
    }

    async function deleteTimeEntry(entry) {
        const url = buildPath(paths.timeEntryDelete, { entryId: entry.id });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
            });
            if (!response.ok) throw new Error();
            await reloadDetail();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    // ── Attachments ──────────────────────────────────────────────────────────
    /** Attach an array of media IDs (already uploaded somewhere — e.g. a media picker). */
    async function attachMedia(mediaIds) {
        if (!editingTask.value || !mediaIds.length) return;
        const url = buildPath(paths.attachmentsAttach, {
            taskId: editingTask.value.id,
        });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ mediaIds }),
            });
            if (!response.ok) throw new Error();
            await reloadDetail();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    async function detachMedia(media) {
        if (!editingTask.value) return;
        const url = buildPath(paths.attachmentDetach, {
            taskId: editingTask.value.id,
            mediaId: media.id,
        });
        try {
            const response = await fetch(url, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
            });
            if (!response.ok) throw new Error();
            await reloadDetail();
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    return {
        newCommentContent,
        commentErrors,
        commentLoading,
        submitComment,
        deleteComment,

        localItems,
        newItemLabel,
        itemErrors,
        addItem,
        toggleItem,
        removeItem,

        newTimeEntry,
        timeErrors,
        timeLoading,
        logTime,
        deleteTimeEntry,

        attachMedia,
        detachMedia,
    };
}
