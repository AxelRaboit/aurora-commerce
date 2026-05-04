<script setup>
import { useI18n } from "vue-i18n";

const { t } = useI18n();

defineProps({
    logoutPath: { type: String, required: true },
    user: { type: Object, required: true },
});
</script>

<template>
    <div class="max-w-lg mx-auto py-12 px-4">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-primary">{{ t('front.account.heading') }}</h1>
            <form method="POST" :action="logoutPath">
                <button type="submit" class="text-sm text-secondary hover:text-danger transition-colors">
                    {{ t('front.account.logout') }}
                </button>
            </form>
        </div>

        <div class="bg-surface border border-line/60 rounded-xl p-6 space-y-4">
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('front.account.name') }}</span>
                <span class="text-sm text-primary font-medium">{{ user.name }}</span>
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('front.account.email') }}</span>
                <span class="text-sm text-primary">{{ user.email }}</span>
            </div>
        </div>
    </div>
</template>
