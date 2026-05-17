<script setup>
import { useI18n } from "vue-i18n";
import { Plus, Trash2, Send, MessageSquare, Wrench } from "lucide-vue-next";
import AppButton from "@shared/components/action/AppButton.vue";
import { useAssistant } from "./composables/useAssistant.js";

const props = defineProps({
    conversations: { type: Array, required: true },
    model: { type: String, required: true },
    listPath: { type: String, required: true },
    showPath: { type: String, required: true },
    createPath: { type: String, required: true },
    sendPath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const { t } = useI18n();

const {
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
} = useAssistant(props);

function onKeydown(event) {
    if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        sendDraft();
    }
}

function bubbleClass(role) {
    if (role === "user") return "bg-accent-500 text-white ml-auto";
    if (role === "tool") return "bg-surface-2 text-secondary font-mono text-xs";
    return "bg-surface border border-line text-primary";
}
</script>

<template>
    <div class="flex h-[calc(100vh-9rem)] gap-4">
        <!-- Sidebar -->
        <aside class="w-72 shrink-0 flex flex-col bg-surface border border-line rounded-xl overflow-hidden">
            <div class="p-3 border-b border-line">
                <AppButton variant="primary" size="sm" class="w-full" v-on:click="newConversation">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('assistant.chat.new') }}
                </AppButton>
                <p class="text-xs text-muted mt-2 truncate" :title="props.model">{{ props.model }}</p>
            </div>
            <div class="flex-1 overflow-y-auto">
                <ul class="divide-y divide-line">
                    <li
                        v-for="conv in conversations"
                        :key="conv.id"
                        class="px-3 py-2 cursor-pointer hover:bg-surface-2 flex items-center gap-2"
                        :class="activeId === conv.id ? 'bg-surface-2' : ''"
                        v-on:click="selectConversation(conv.id)"
                    >
                        <MessageSquare class="w-4 h-4 text-muted shrink-0" :stroke-width="1.5" />
                        <span class="flex-1 text-sm text-primary truncate">{{ conv.title || t('assistant.chat.untitled') }}</span>
                        <button
                            type="button"
                            class="text-muted hover:text-red-500 shrink-0"
                            :title="t('assistant.chat.delete')"
                            v-on:click.stop="deleteConversation(conv.id)"
                        >
                            <Trash2 class="w-4 h-4" :stroke-width="1.5" />
                        </button>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Chat panel -->
        <section class="flex-1 flex flex-col bg-surface border border-line rounded-xl overflow-hidden">
            <div v-if="!activeConversation" class="flex-1 flex items-center justify-center text-muted text-sm">
                {{ t('assistant.chat.empty') }}
            </div>
            <template v-else>
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <div v-for="msg in activeMessages" :key="msg.id" class="flex">
                        <div
                            class="max-w-[80%] rounded-lg px-3 py-2 whitespace-pre-wrap wrap-break-word"
                            :class="bubbleClass(msg.role)"
                        >
                            <div v-if="msg.role === 'tool'" class="flex items-center gap-1 text-muted text-[10px] uppercase tracking-wide mb-1">
                                <Wrench class="w-3 h-3" :stroke-width="2" />
                                {{ msg.toolName || t('assistant.chat.tool_result') }}
                            </div>
                            {{ msg.content }}
                            <div
                                v-if="msg.role === 'assistant' && msg.toolCalls && msg.toolCalls.length"
                                class="mt-2 text-[10px] text-muted uppercase tracking-wide flex items-center gap-1"
                            >
                                <Wrench class="w-3 h-3" :stroke-width="2" />
                                {{ t('assistant.chat.tool_call') }}: {{ msg.toolCalls.map(c => c.function?.name).filter(Boolean).join(', ') }}
                            </div>
                        </div>
                    </div>
                    <div v-if="sending" class="text-xs text-muted italic">{{ t('assistant.chat.thinking') }}</div>
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
    </div>
</template>
