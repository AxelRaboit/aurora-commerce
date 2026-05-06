<script setup>
import { useI18n } from "vue-i18n";
import { Mail, LogIn } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AuthCard from "@/frontend/components/AuthCard.vue";

const { t } = useI18n();

defineProps({
    loginPath: { type: String, required: true },
    resendPath: { type: String, required: true },
    pendingEmail: { type: String, default: "" },
    resent: { type: Boolean, default: false },
});
</script>

<template>
    <AuthCard
        :heading="t('frontend.register.confirm_heading')"
        :subtitle="t('frontend.register.confirm_message')"
    >
        <template #icon><Mail class="w-6 h-6" :stroke-width="2" /></template>
        <div class="text-center">
            <AppButton variant="accent" :href="loginPath">
                <LogIn class="w-4 h-4" :stroke-width="2" />
                {{ t('frontend.login.submit') }}
            </AppButton>

            <div v-if="pendingEmail" class="mt-8 pt-8 border-t border-line/40">
                <p v-if="resent" class="text-sm text-success">{{ t('frontend.register.resent_success') }}</p>
                <template v-else>
                    <p class="text-sm text-secondary mb-3">{{ t('frontend.register.resend_hint') }}</p>
                    <form method="POST" :action="resendPath">
                        <AppButton type="submit" variant="link-accent" size="none">
                            <Mail class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t('frontend.register.resend_link') }}
                        </AppButton>
                    </form>
                </template>
            </div>
        </div>
    </AuthCard>
</template>
