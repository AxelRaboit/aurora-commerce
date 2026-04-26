<script setup>
import { useI18n } from "vue-i18n";
import { Check, X } from "lucide-vue-next";
import AppButton from "@/components/AppButton.vue";

const { t } = useI18n();

defineProps({
    success: { type: Boolean, required: true },
    loginPath: { type: String, required: true },
    registerPath: { type: String, required: true },
});
</script>

<template>
    <div class="max-w-md mx-auto py-16 px-4 text-center">
        <template v-if="success">
            <div class="w-14 h-14 rounded-full bg-success-soft flex items-center justify-center mx-auto mb-6">
                <Check class="w-7 h-7 text-success" :stroke-width="2" />
            </div>
            <h1 class="text-2xl font-bold text-primary mb-3">{{ t('front.verify_email.success_heading') }}</h1>
            <p class="text-secondary text-sm mb-8">{{ t('front.verify_email.success_message') }}</p>
            <AppButton :href="loginPath" variant="accent">
                {{ t('front.login.submit') }}
            </AppButton>
        </template>
        <template v-else>
            <div class="w-14 h-14 rounded-full bg-danger-soft flex items-center justify-center mx-auto mb-6">
                <X class="w-7 h-7 text-danger" :stroke-width="2" />
            </div>
            <h1 class="text-2xl font-bold text-primary mb-3">{{ t('front.verify_email.error_heading') }}</h1>
            <p class="text-secondary text-sm mb-8">{{ t('front.verify_email.error_message') }}</p>
            <AppButton :href="registerPath" variant="secondary">
                {{ t('front.register.submit') }}
            </AppButton>
        </template>
    </div>
</template>
