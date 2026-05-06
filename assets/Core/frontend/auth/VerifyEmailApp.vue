<script setup>
import { useI18n } from "vue-i18n";
import { Check, X, LogIn, UserPlus } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AuthCard from "@/frontend/components/AuthCard.vue";

const { t } = useI18n();

defineProps({
    success: { type: Boolean, required: true },
    loginPath: { type: String, required: true },
    registerPath: { type: String, required: true },
});
</script>

<template>
    <AuthCard
        :heading="success ? t('frontend.verify_email.success_heading') : t('frontend.verify_email.error_heading')"
        :subtitle="success ? t('frontend.verify_email.success_message') : t('frontend.verify_email.error_message')"
    >
        <template #icon>
            <Check v-if="success" class="w-6 h-6" :stroke-width="2" />
            <X v-else class="w-6 h-6" :stroke-width="2" />
        </template>
        <div class="text-center">
            <AppButton v-if="success" :href="loginPath" variant="accent">
                <LogIn class="w-4 h-4" :stroke-width="2" />
                {{ t('frontend.login.submit') }}
            </AppButton>
            <AppButton v-else :href="registerPath" variant="secondary">
                <UserPlus class="w-4 h-4" :stroke-width="2" />
                {{ t('frontend.register.submit') }}
            </AppButton>
        </div>
    </AuthCard>
</template>
