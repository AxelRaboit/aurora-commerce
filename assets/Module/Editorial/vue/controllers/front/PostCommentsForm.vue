<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/AppButton.vue";

const { t } = useI18n();

defineProps({
    parentId: { type: Number, default: null },
    submitting: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    authorName: { type: String, default: "" },
    authorEmail: { type: String, default: "" },
    content: { type: String, default: "" },
});

defineEmits(["update:authorName", "update:authorEmail", "update:content", "submit", "cancel"]);

const inputClass = "w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary focus:outline-none focus:ring-2 focus:ring-accent-500";
</script>

<template>
    <form class="space-y-3" v-on:submit.prevent="$emit('submit')">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ t("shared.comment.name") }}</label>
                <input
                    :class="inputClass"
                    type="text"
                    :value="authorName"
                    required
                    maxlength="100"
                    v-on:input="$emit('update:authorName', $event.target.value)"
                >
                <p v-if="errors.authorName" class="mt-1 text-xs text-rose-500">{{ errors.authorName }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ t("shared.comment.email") }}</label>
                <input
                    :class="inputClass"
                    type="email"
                    :value="authorEmail"
                    required
                    v-on:input="$emit('update:authorEmail', $event.target.value)"
                >
                <p v-if="errors.authorEmail" class="mt-1 text-xs text-rose-500">{{ errors.authorEmail }}</p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ t("shared.comment.content") }}</label>
            <textarea
                :class="inputClass + ' resize-y'"
                :value="content"
                required
                rows="3"
                maxlength="2000"
                v-on:input="$emit('update:content', $event.target.value)"
            />
            <p v-if="errors.content" class="mt-1 text-xs text-rose-500">{{ errors.content }}</p>
        </div>
        <div class="flex items-center gap-2">
            <AppButton type="submit" variant="primary" size="md" :loading="submitting">
                {{ t("shared.comment.submit") }}
            </AppButton>
            <AppButton
                v-if="parentId !== null"
                type="button"
                variant="ghost"
                size="md"
                v-on:click="$emit('cancel')"
            >
                {{ t("shared.common.cancel") }}
            </AppButton>
        </div>
    </form>
</template>
