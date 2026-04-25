<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from "vue";
import { useI18n } from "vue-i18n";
import { useDateFormat } from "@/composables/useDateFormat.js";
import PostCommentsForm from "./PostCommentsForm.vue";
import PostCommentsReactionBar from "./PostCommentsReactionBar.vue";

const props = defineProps({
    listPath: { type: String, required: true },
    submitPath: { type: String, required: true },
    reactPathTemplate: { type: String, required: true },
    commentsEnabled: { type: Boolean, default: false },
});

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const roots = ref([]);
const replies = ref({});
const reactionEmojis = ref({});
const loading = ref(true);
const successMessage = ref("");
let successTimeout = null;
const errors = ref({});
const submitting = ref(false);
const replyOpenFor = ref(null);
const form = reactive({ authorName: "", authorEmail: "", content: "" });
const mainForm = reactive({ authorName: "", authorEmail: "", content: "" });

async function fetchComments() {
    loading.value = true;
    try {
        const response = await fetch(props.listPath);
        const data = await response.json();
        if (data.ok) {
            roots.value = data.roots;
            replies.value = data.replies;
            reactionEmojis.value = data.reactionEmojis;
        }
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    if (props.commentsEnabled) fetchComments();
});

onBeforeUnmount(() => {
    clearTimeout(successTimeout);
});

function openReply(commentId) {
    replyOpenFor.value = replyOpenFor.value === commentId ? null : commentId;
    form.authorName = "";
    form.authorEmail = "";
    form.content = "";
    errors.value = {};
}

async function submitComment(parentId, activeForm) {
    submitting.value = true;
    errors.value = {};
    try {
        const response = await fetch(props.submitPath, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                authorName: activeForm.authorName,
                authorEmail: activeForm.authorEmail,
                content: activeForm.content,
                parent_id: parentId ?? 0,
            }),
        });
        const data = await response.json();
        if (!data.ok) {
            errors.value = data.errors ?? {};
            return;
        }
        activeForm.authorName = "";
        activeForm.authorEmail = "";
        activeForm.content = "";
        replyOpenFor.value = null;
        successMessage.value = t("comment.success");
        await fetchComments();
        clearTimeout(successTimeout);
        successTimeout = setTimeout(() => { successMessage.value = ""; }, 5000);
    } finally {
        submitting.value = false;
    }
}

async function react(commentId, type) {
    const url = props.reactPathTemplate.replace("__commentId__", String(commentId));
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ type }),
        });
        const data = await response.json();
        if (!data.ok) return;
        const update = (list) => list.map((c) =>
            c.id === commentId ? { ...c, reactionCounts: data.counts } : c
        );
        roots.value = update(roots.value);
        const updated = {};
        for (const [rootId, list] of Object.entries(replies.value)) {
            updated[rootId] = update(list);
        }
        replies.value = updated;
    } catch {}
}

function formatDate(iso) {
    return formatDateShort(iso);
}

</script>

<template>
    <div v-if="commentsEnabled" class="max-w-3xl mx-auto mt-12 pt-8 border-t border-line">
        <h2 class="text-2xl font-bold text-primary mb-6">{{ t("comment.title") }}</h2>

        <div v-if="successMessage" class="mb-6 p-4 rounded-lg bg-emerald-500/15 text-emerald-600 text-sm">
            {{ successMessage }}
        </div>

        <div v-if="loading" class="text-muted text-sm mb-8">{{ t("common.loading") }}</div>

        <template v-else>
            <p v-if="!roots.length" class="text-muted text-sm mb-8">{{ t("comment.empty") }}</p>

            <div v-else class="space-y-6 mb-10">
                <div v-for="rootComment in roots" :key="rootComment.id" class="bg-surface-2 rounded-lg p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="font-semibold text-primary text-sm">{{ rootComment.authorName }}</span>
                        <time class="text-xs text-muted">{{ formatDate(rootComment.createdAt) }}</time>
                    </div>
                    <p class="text-secondary text-sm leading-relaxed">{{ rootComment.content }}</p>

                    <PostCommentsReactionBar :comment="rootComment" :reaction-emojis="reactionEmojis" v-on:react="react" />

                    <!-- Flat replies -->
                    <div v-if="replies[rootComment.id]?.length" class="mt-4 ml-6 space-y-3 border-l-2 border-line pl-4">
                        <div v-for="reply in replies[rootComment.id]" :key="reply.id" class="bg-surface rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="font-semibold text-primary text-sm">{{ reply.authorName }}</span>
                                <span v-if="reply.parentAuthorName" class="text-xs text-muted">↩ {{ reply.parentAuthorName }}</span>
                                <time class="text-xs text-muted ml-auto">{{ formatDate(reply.createdAt) }}</time>
                            </div>
                            <p class="text-secondary text-sm leading-relaxed">{{ reply.content }}</p>

                            <PostCommentsReactionBar :comment="reply" :reaction-emojis="reactionEmojis" v-on:react="react" />

                            <button type="button" class="mt-2 text-xs text-muted hover:text-primary transition-colors" v-on:click="openReply(reply.id)">
                                ↩ {{ t("comment.reply") }}
                            </button>
                            <div v-if="replyOpenFor === reply.id" class="mt-3">
                                <PostCommentsForm
                                    :parent-id="reply.id"
                                    :submitting="submitting"
                                    :errors="errors"
                                    :author-name="form.authorName"
                                    :author-email="form.authorEmail"
                                    :content="form.content"
                                    v-on:update:author-name="form.authorName = $event"
                                    v-on:update:author-email="form.authorEmail = $event"
                                    v-on:update:content="form.content = $event"
                                    v-on:submit="submitComment(reply.id, form)"
                                    v-on:cancel="replyOpenFor = null"
                                />
                            </div>
                        </div>
                    </div>

                    <button type="button" class="mt-3 text-xs text-muted hover:text-primary transition-colors" v-on:click="openReply(rootComment.id)">
                        ↩ {{ t("comment.reply") }}
                    </button>
                    <div v-if="replyOpenFor === rootComment.id" class="mt-3">
                        <PostCommentsForm
                            :parent-id="rootComment.id"
                            :submitting="submitting"
                            :errors="errors"
                            :author-name="form.authorName"
                            :author-email="form.authorEmail"
                            :content="form.content"
                            v-on:update:author-name="form.authorName = $event"
                            v-on:update:author-email="form.authorEmail = $event"
                            v-on:update:content="form.content = $event"
                            v-on:submit="submitComment(rootComment.id, form)"
                            v-on:cancel="replyOpenFor = null"
                        />
                    </div>
                </div>
            </div>
        </template>

        <!-- Main comment form -->
        <div class="bg-surface border border-line rounded-xl p-6">
            <h3 class="text-lg font-semibold text-primary mb-4">{{ t("comment.form_title") }}</h3>
            <PostCommentsForm
                :parent-id="null"
                :submitting="submitting"
                :errors="errors"
                :author-name="mainForm.authorName"
                :author-email="mainForm.authorEmail"
                :content="mainForm.content"
                v-on:update:author-name="mainForm.authorName = $event"
                v-on:update:author-email="mainForm.authorEmail = $event"
                v-on:update:content="mainForm.content = $event"
                v-on:submit="submitComment(null, mainForm)"
            />
        </div>
    </div>
</template>
