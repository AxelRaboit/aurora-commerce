<script setup>
import { useI18n } from "vue-i18n";
import { Plus, Trash2, Send, MessageSquare, Wrench, X, ChevronRight } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { useAssistant } from "./composables/useAssistant.js";

const props = defineProps({
    conversations: { type: Array, required: true },
    model: { type: String, required: true },
    listPath: { type: String, required: true },
    showPath: { type: String, required: true },
    createPath: { type: String, required: true },
    sendPath: { type: String, required: true },
    confirmToolPath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const { t } = useI18n();

const {
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
} = useAssistant(props);

function onKeydown(event) {
    if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        sendDraft();
    }
}

function toolSummary(content) {
    const firstLine = (content ?? "").split("\n", 1)[0]?.trim() ?? "";
    return firstLine.length > 80 ? firstLine.slice(0, 77) + "…" : firstLine;
}

function bubbleClass(role) {
    if (role === "user") return "bg-accent-500 text-white ml-auto";
    if (role === "tool") return "bg-surface-2 text-secondary font-mono text-xs";
    return "bg-surface border border-line text-primary";
}
</script>

<template>
    <div class="flex h-[calc(100vh-9rem)] gap-4">
        <aside class="w-72 shrink-0 flex flex-col bg-surface border border-line rounded-xl overflow-hidden">
            <div class="p-3 border-b border-line">
                <AppButton variant="primary" size="sm" class="w-full" v-on:click="newConversation">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('assistant.chat.new') }}
                </AppButton>
                <p class="text-xs text-muted mt-2 truncate" :title="props.model">{{ props.model }}</p>
            </div>
            <div class="flex-1 overflow-y-auto">
                <AppNoData v-if="!conversations.length" :message="t('assistant.chat.no_conversations')" />
                <ul v-else class="divide-y divide-line">
                    <li
                        v-for="conv in conversations"
                        :key="conv.id"
                        class="px-3 py-2 cursor-pointer hover:bg-surface-2 flex items-center gap-2 group"
                        :class="activeId === conv.id ? 'bg-surface-2' : ''"
                        v-on:click="selectConversation(conv.id)"
                    >
                        <MessageSquare class="w-4 h-4 text-muted shrink-0" :stroke-width="1.5" />
                        <span class="flex-1 text-sm text-primary truncate">{{ conv.title || t('assistant.chat.untitled') }}</span>
                        <AppIconButton
                            color="rose"
                            :title="t('shared.common.delete')"
                            class="opacity-0 group-hover:opacity-100 transition-opacity"
                            v-on:click.stop="deletingConversation = conv"
                        >
                            <Trash2 class="w-4 h-4" :stroke-width="1.5" />
                        </AppIconButton>
                    </li>
                </ul>
            </div>
        </aside>

        <section class="flex-1 flex flex-col bg-surface border border-line rounded-xl overflow-hidden">
            <div v-if="!activeConversation" class="flex-1 flex items-center justify-center text-muted text-sm">
                {{ t('assistant.chat.empty') }}
            </div>
            <template v-else>
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <template v-for="msg in activeMessages" :key="msg.id">
                        <details
                            v-if="msg.role === 'tool'"
                            class="group rounded-md border border-line bg-surface-2/60 text-xs max-w-[80%]"
                        >
                            <summary class="flex items-center gap-2 px-2 py-1 cursor-pointer select-none text-muted hover:text-primary list-none">
                                <ChevronRight class="w-3 h-3 transition-transform group-open:rotate-90" :stroke-width="2" />
                                <Wrench class="w-3 h-3" :stroke-width="2" />
                                <span class="font-mono">{{ msg.toolName || t('assistant.chat.tool_result') }}</span>
                                <span class="text-muted/70 truncate">— {{ toolSummary(msg.content) }}</span>
                            </summary>
                            <pre class="px-3 py-2 border-t border-line/60 text-secondary font-mono whitespace-pre-wrap wrap-break-word">{{ msg.content }}</pre>
                        </details>

                        <div v-else-if="msg.content && msg.content.trim()" class="flex">
                            <div
                                class="max-w-[80%] rounded-lg px-3 py-2 whitespace-pre-wrap wrap-break-word"
                                :class="bubbleClass(msg.role)"
                            >
                                {{ msg.content }}
                            </div>
                        </div>
                    </template>
                    <div v-if="sending" class="text-xs text-muted italic">{{ t('assistant.chat.thinking') }}</div>

                    <div
                        v-if="pendingMessage"
                        class="rounded-lg border border-amber-500/40 bg-amber-50 dark:bg-amber-950/30 p-3 space-y-2"
                    >
                        <div class="text-xs font-semibold text-amber-700 dark:text-amber-300 uppercase tracking-wide">
                            {{ t('assistant.chat.confirm_required') }}
                        </div>
                        <div
                            v-for="(call, i) in pendingMessage.toolCalls"
                            :key="call.id || i"
                            class="rounded bg-white dark:bg-surface-2 border border-line p-2 text-xs"
                        >
                            <div class="font-mono font-semibold text-primary">
                                {{ call.function?.name }}
                            </div>
                            <pre class="mt-1 text-xs text-secondary whitespace-pre-wrap wrap-break-word">{{ JSON.stringify(call.function?.arguments ?? {}, null, 2) }}</pre>
                        </div>
                        <div class="flex gap-2">
                            <AppButton variant="primary" size="sm" :disabled="sending" v-on:click="approvePendingCalls">
                                {{ t('assistant.chat.approve') }}
                            </AppButton>
                            <AppButton variant="ghost" size="sm" :disabled="sending" v-on:click="rejectPendingCalls">
                                {{ t('assistant.chat.reject') }}
                            </AppButton>
                        </div>
                    </div>
                </div>
            </template>

            <div class="border-t border-line p-3 flex gap-2">
                <textarea
                    v-model="draft"
                    rows="2"
                    class="flex-1 rounded-lg border border-line bg-surface-2 px-3 py-2 text-sm text-primary resize-none focus:outline-none focus:ring-2 focus:ring-accent-500/40"
                    :placeholder="t('assistant.chat.placeholder')"
                    :disabled="sending"
                    v-on:keydown="onKeydown"
                />
                <AppButton variant="primary" size="md" :disabled="sending || !draft.trim()" v-on:click="sendDraft">
                    <Send class="w-4 h-4" :stroke-width="2" />
                    {{ t('assistant.chat.send') }}
                </AppButton>
            </div>
        </section>

        <AppModal
            :show="!!deletingConversation"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingConversation = null"
        >
            <p class="text-sm text-primary">
                {{ t('assistant.chat.delete_confirm', { title: deletingConversation?.title || t('assistant.chat.untitled') }) }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingConversation = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.cancel') }}
                    </AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDeleteConversation">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.delete') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
