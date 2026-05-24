import { ref, watch } from "vue";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { required } from "@/shared/utils/validation/validators.js";

/**
 * Bundle of inline task-edit sub-features (comments, checklist, time entries,
 * attachments) — all bound to the same `editingTask` ref. State is local to
 * the composable; each action persists immediately and reloads the detail.
 */
export function useTaskExtras(paths, editingTask, reloadDetail) {
    const { t } = useI18n();
    const { request } = useRequest();

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
        const data = await request(url, { content });
        commentLoading.value = false;
        if (!data) return;
        if (!data.success) return;
        newCommentContent.value = "";
        clearCommentErrors();
        await reloadDetail();
    }

    async function deleteComment(comment) {
        const url = buildPath(paths.commentDelete, { commentId: comment.id });
        const data = await request(url);
        if (!data) return;
        await reloadDetail();
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
        const data = await request(url, { items: payload });
        if (!data) return;
        await reloadDetail();
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
        const data = await request(url, newTimeEntry.value);
        timeLoading.value = false;
        if (!data) return;
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
    }

    async function deleteTimeEntry(entry) {
        const url = buildPath(paths.timeEntryDelete, { entryId: entry.id });
        const data = await request(url);
        if (!data) return;
        await reloadDetail();
    }

    // ── Attachments ──────────────────────────────────────────────────────────
    /** Attach an array of GED document IDs (picked via DocumentPickerModal). */
    async function attachDocument(documentIds) {
        if (!editingTask.value || !documentIds.length) return;
        const url = buildPath(paths.attachmentsAttach, {
            taskId: editingTask.value.id,
        });
        const data = await request(url, { documentIds });
        if (!data) return;
        await reloadDetail();
    }

    async function detachDocument(document) {
        if (!editingTask.value) return;
        const url = buildPath(paths.attachmentDetach, {
            taskId: editingTask.value.id,
            documentId: document.id,
        });
        const data = await request(url);
        if (!data) return;
        await reloadDetail();
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

        attachDocument,
        detachDocument,
    };
}
