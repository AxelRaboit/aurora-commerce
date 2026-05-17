import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

function resolvePath(template, id) {
    return template.replace("__id__", String(id));
}

export function useAssistant(props) {
    const { t } = useI18n();
    const { request } = useRequest();

    const conversations = ref([...props.conversations]);
    const activeId = ref(null);
    const activeConversation = ref(null);
    const sending = ref(false);
    const draft = ref("");

    const activeMessages = computed(
        () => activeConversation.value?.messages ?? [],
    );

    async function refreshList() {
        const res = await request(props.listPath, null, HttpMethod.Get);
        if (res?.success) {
            conversations.value = res.data.conversations;
        }
    }

    async function selectConversation(id) {
        activeId.value = id;
        activeConversation.value = null;
        const res = await request(
            resolvePath(props.showPath, id),
            null,
            HttpMethod.Get,
        );
        if (res?.success) {
            activeConversation.value = res.data.conversation;
        }
    }

    async function newConversation() {
        const res = await request(props.createPath);
        if (res?.success) {
            activeConversation.value = res.data.conversation;
            activeId.value = res.data.conversation.id;
            await refreshList();
        }
    }

    async function sendDraft() {
        const content = draft.value.trim();
        if (!content || sending.value) return;

        if (!activeConversation.value) {
            await newConversation();
            if (!activeConversation.value) return;
        }

        sending.value = true;
        const url = resolvePath(props.sendPath, activeConversation.value.id);
        draft.value = "";
        try {
            const res = await request(url, { content });
            if (res?.success) {
                activeConversation.value = res.data.conversation;
                await refreshList();
            } else {
                toast.error(t("assistant.errors.message_empty"));
            }
        } finally {
            sending.value = false;
        }
    }

    async function deleteConversation(id) {
        if (!window.confirm(t("assistant.chat.delete_confirm"))) return;
        const res = await request(resolvePath(props.deletePath, id));
        if (res?.success) {
            if (activeId.value === id) {
                activeId.value = null;
                activeConversation.value = null;
            }

            await refreshList();
        }
    }

    return {
        conversations,
        activeId,
        activeConversation,
        activeMessages,
        sending,
        draft,
        selectConversation,
        newConversation,
        sendDraft,
        deleteConversation,
    };
}
