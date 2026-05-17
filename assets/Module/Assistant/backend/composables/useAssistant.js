import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

/**
 * State machine + I/O for the AI assistant chat panel.
 *
 * Owns: conversation list, currently-loaded conversation, the in-flight
 * draft, the pending-confirmation pointer, and every HTTP roundtrip the
 * panel makes. The SFC consumes these as refs/computed and renders only.
 *
 * Optimistic updates: create/delete mutate the local `conversations`
 * array directly so the sidebar reacts without a list refetch. The list
 * is only refetched when timestamps are likely to have shifted (after
 * a successful send, which updates `updatedAt`).
 */
export function useAssistant(props) {
    const { t } = useI18n();
    const { request } = useRequest();

    const conversations = ref([...props.conversations]);
    const activeId = ref(null);
    const activeConversation = ref(null);
    const sending = ref(false);
    const draft = ref("");
    const deletingConversation = ref(null);

    const activeMessages = computed(
        () => activeConversation.value?.messages ?? [],
    );

    const pendingMessage = computed(
        () =>
            activeMessages.value.find(
                (m) => m.role === "assistant" && m.awaitingConfirmation,
            ) ?? null,
    );

    async function refreshList() {
        const data = await request(props.listPath, null, HttpMethod.Get);
        if (data?.success) {
            conversations.value = data.data.conversations;
        }
    }

    async function selectConversation(id) {
        activeId.value = id;
        activeConversation.value = null;
        const data = await request(
            buildPath(props.showPath, { id }),
            null,
            HttpMethod.Get,
        );
        if (data?.success) {
            activeConversation.value = data.data.conversation;
        }
    }

    async function newConversation() {
        const data = await request(props.createPath);
        if (!data?.success) return;

        const conversation = data.data.conversation;
        activeConversation.value = conversation;
        activeId.value = conversation.id;

        // Optimistic push: insert at the top so the sidebar reacts
        // immediately, without a second roundtrip.
        conversations.value = [
            {
                id: conversation.id,
                title: conversation.title,
                model: conversation.model,
                createdAt: conversation.createdAt,
                updatedAt: conversation.updatedAt,
            },
            ...conversations.value,
        ];
    }

    async function sendDraft() {
        const content = draft.value.trim();
        if (!content || sending.value) return;

        if (!activeConversation.value) {
            await newConversation();
            if (!activeConversation.value) return;
        }

        sending.value = true;
        const url = buildPath(props.sendPath, {
            id: activeConversation.value.id,
        });
        draft.value = "";
        try {
            const data = await request(url, { content });
            if (data?.success) {
                activeConversation.value = data.data.conversation;
                await refreshList();
            } else {
                toast.error(t("assistant.errors.message_empty"));
            }
        } finally {
            sending.value = false;
        }
    }

    async function confirmTool(decisions) {
        if (!activeConversation.value || sending.value) return;
        sending.value = true;
        try {
            const url = buildPath(props.confirmToolPath, {
                id: activeConversation.value.id,
            });
            const data = await request(url, { decisions });
            if (data?.success) {
                activeConversation.value = data.data.conversation;
                await refreshList();
            }
        } finally {
            sending.value = false;
        }
    }

    function approvePendingCalls() {
        const decisions = buildDecisions("approve");
        if (decisions) confirmTool(decisions);
    }

    function rejectPendingCalls() {
        const decisions = buildDecisions("reject");
        if (decisions) confirmTool(decisions);
    }

    function buildDecisions(verdict) {
        const calls = pendingMessage.value?.toolCalls ?? [];
        if (!calls.length) return null;
        return Object.fromEntries(
            calls.map((call, i) => [call.id ?? String(i), verdict]),
        );
    }

    async function confirmDeleteConversation() {
        if (!deletingConversation.value) return;
        const id = deletingConversation.value.id;
        const data = await request(buildPath(props.deletePath, { id }));
        if (!data?.success) return;

        conversations.value = conversations.value.filter((c) => c.id !== id);
        if (activeId.value === id) {
            activeId.value = null;
            activeConversation.value = null;
        }

        toast.success(t("shared.common.deleted"));
        deletingConversation.value = null;
    }

    return {
        conversations,
        activeId,
        activeConversation,
        activeMessages,
        pendingMessage,
        sending,
        draft,
        deletingConversation,
        selectConversation,
        newConversation,
        sendDraft,
        approvePendingCalls,
        rejectPendingCalls,
        confirmDeleteConversation,
    };
}
