<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, defineComponent, h } from "vue";
import { useI18n } from "vue-i18n";

const props = defineProps({
    listPath: { type: String, required: true },
    submitPath: { type: String, required: true },
    reactPathTemplate: { type: String, required: true },
    commentsEnabled: { type: Boolean, default: false },
});

const { t } = useI18n();

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
    return new Date(iso).toLocaleDateString();
}

// ── Sub-components ────────────────────────────────────────────────────────────
const CommentForm = defineComponent({
    props: {
        parentId: { default: null },
        submitting: { type: Boolean, default: false },
        errors: { type: Object, default: () => ({}) },
        authorName: { type: String, default: "" },
        authorEmail: { type: String, default: "" },
        content: { type: String, default: "" },
    },
    emits: ["update:authorName", "update:authorEmail", "update:content", "submit", "cancel"],
    setup(props, { emit }) {
        const { t } = useI18n();
        const inputClass = "w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary focus:outline-none focus:ring-2 focus:ring-indigo-500";
        return () => h("form", { class: "space-y-3", onSubmit: (e) => { e.preventDefault(); emit("submit"); } }, [
            h("div", { class: "grid grid-cols-1 sm:grid-cols-2 gap-3" }, [
                h("div", [
                    h("label", { class: "block text-sm font-medium text-secondary mb-1" }, t("comment.name")),
                    h("input", { type: "text", value: props.authorName, required: true, maxlength: 100, class: inputClass, onInput: (e) => emit("update:authorName", e.target.value) }),
                    props.errors.authorName ? h("p", { class: "mt-1 text-xs text-rose-500" }, props.errors.authorName) : null,
                ]),
                h("div", [
                    h("label", { class: "block text-sm font-medium text-secondary mb-1" }, t("comment.email")),
                    h("input", { type: "email", value: props.authorEmail, required: true, class: inputClass, onInput: (e) => emit("update:authorEmail", e.target.value) }),
                    props.errors.authorEmail ? h("p", { class: "mt-1 text-xs text-rose-500" }, props.errors.authorEmail) : null,
                ]),
            ]),
            h("div", [
                h("label", { class: "block text-sm font-medium text-secondary mb-1" }, t("comment.content")),
                h("textarea", { value: props.content, required: true, rows: 3, maxlength: 2000, class: inputClass + " resize-y", onInput: (e) => emit("update:content", e.target.value) }),
                props.errors.content ? h("p", { class: "mt-1 text-xs text-rose-500" }, props.errors.content) : null,
            ]),
            h("div", { class: "flex items-center gap-2" }, [
                h("button", { type: "submit", disabled: props.submitting, class: "inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-medium transition-colors" },
                  props.submitting ? t("common.loading") : t("comment.submit")),
                props.parentId !== null ? h("button", { type: "button", class: "text-sm text-muted hover:text-primary transition-colors", onClick: () => emit("cancel") }, t("common.cancel")) : null,
            ]),
        ]);
    },
});

const ReactionBar = defineComponent({
    props: {
        comment: { type: Object, required: true },
        reactionEmojis: { type: Object, default: () => ({}) },
    },
    emits: ["react"],
    setup(props, { emit }) {
        const { t } = useI18n();
        const btnClass = "inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-surface-2 hover:bg-surface-3 border border-line transition-colors";
        return () => h("div", { class: "flex items-center gap-1 mt-3 flex-wrap" }, [
            ...Object.entries(props.reactionEmojis).map(([type, emoji]) =>
                (props.comment.reactionCounts?.[type] ?? 0) > 0
                    ? h("button", { type: "button", class: btnClass, onClick: () => emit("react", props.comment.id, type) },
                        [emoji + " " + props.comment.reactionCounts[type]])
                    : null
            ).filter(Boolean),
            h("div", { class: "relative group" }, [
                h("button", { type: "button", class: btnClass + " text-muted hover:text-primary" }, ["＋ ", t("comment.react")]),
                h("div", { class: "hidden group-hover:flex absolute left-0 top-full mt-1 z-10 gap-1 p-2 bg-surface border border-line rounded-xl shadow-lg" },
                  Object.entries(props.reactionEmojis).map(([type, emoji]) =>
                      h("button", { type: "button", class: "text-lg hover:scale-125 transition-transform p-1", onClick: () => emit("react", props.comment.id, type) }, emoji)
                  )
                ),
            ]),
        ]);
    },
});
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
                <div v-for="comment in roots" :key="comment.id" class="bg-surface-2 rounded-lg p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="font-semibold text-primary text-sm">{{ comment.authorName }}</span>
                        <time class="text-xs text-muted">{{ formatDate(comment.createdAt) }}</time>
                    </div>
                    <p class="text-secondary text-sm leading-relaxed">{{ comment.content }}</p>

                    <ReactionBar :comment="comment" :reaction-emojis="reactionEmojis" v-on:react="react" />

                    <!-- Flat replies -->
                    <div v-if="replies[comment.id]?.length" class="mt-4 ml-6 space-y-3 border-l-2 border-line pl-4">
                        <div v-for="reply in replies[comment.id]" :key="reply.id" class="bg-surface rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="font-semibold text-primary text-sm">{{ reply.authorName }}</span>
                                <span v-if="reply.parentAuthorName" class="text-xs text-muted">↩ {{ reply.parentAuthorName }}</span>
                                <time class="text-xs text-muted ml-auto">{{ formatDate(reply.createdAt) }}</time>
                            </div>
                            <p class="text-secondary text-sm leading-relaxed">{{ reply.content }}</p>

                            <ReactionBar :comment="reply" :reaction-emojis="reactionEmojis" v-on:react="react" />

                            <button type="button" class="mt-2 text-xs text-muted hover:text-primary transition-colors" v-on:click="openReply(reply.id)">
                                ↩ {{ t("comment.reply") }}
                            </button>
                            <div v-if="replyOpenFor === reply.id" class="mt-3">
                                <CommentForm
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

                    <button type="button" class="mt-3 text-xs text-muted hover:text-primary transition-colors" v-on:click="openReply(comment.id)">
                        ↩ {{ t("comment.reply") }}
                    </button>
                    <div v-if="replyOpenFor === comment.id" class="mt-3">
                        <CommentForm
                            :parent-id="comment.id"
                            :submitting="submitting"
                            :errors="errors"
                            :author-name="form.authorName"
                            :author-email="form.authorEmail"
                            :content="form.content"
                            v-on:update:author-name="form.authorName = $event"
                            v-on:update:author-email="form.authorEmail = $event"
                            v-on:update:content="form.content = $event"
                            v-on:submit="submitComment(comment.id, form)"
                            v-on:cancel="replyOpenFor = null"
                        />
                    </div>
                </div>
            </div>
        </template>

        <!-- Main comment form -->
        <div class="bg-surface border border-line rounded-xl p-6">
            <h3 class="text-lg font-semibold text-primary mb-4">{{ t("comment.form_title") }}</h3>
            <CommentForm
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
