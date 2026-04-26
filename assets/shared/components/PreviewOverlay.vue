<script setup>
import { useI18n } from "vue-i18n";
import { X } from "lucide-vue-next";
import AppButton from "@/shared/components/AppButton.vue";

const { t } = useI18n();

defineProps({
    show:             { type: Boolean, default: false },
    title:            { type: String,  default: "" },
    html:             { type: String,  default: "" },
    featuredMediaUrl: { type: String,  default: null },
    label:            { type: String,  default: null },
});

defineEmits(["close"]);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex flex-col bg-bg overflow-y-auto scrollbar-thin">
                <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-3 border-b border-line bg-surface/90 backdrop-blur-sm shrink-0">
                    <span class="text-sm font-medium text-secondary">{{ label ?? t("admin.posts.preview") }}</span>
                    <AppButton variant="ghost" size="none" class="p-1.5" v-on:click="$emit('close')">
                        <X class="w-5 h-5" :stroke-width="2" />
                    </AppButton>
                </div>

                <div class="flex-1 w-full max-w-3xl mx-auto px-6 py-12">
                    <img v-if="featuredMediaUrl" :src="featuredMediaUrl" class="w-full max-h-80 object-cover rounded-xl mb-8" alt="">
                    <h1 v-if="title" class="text-3xl font-bold text-primary mb-8">{{ title }}</h1>
                    <div v-if="html" class="prose-preview" v-html="html" />
                    <p v-else class="text-muted text-sm italic">{{ t("admin.posts.previewEmpty") }}</p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
