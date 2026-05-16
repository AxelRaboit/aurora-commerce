<script setup>
import AppLink from "@/shared/components/nav/AppLink.vue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Mail } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AuthCard from "@core/frontend/components/AuthCard.vue";
import { useAuthForm } from "@/shared/composables/form/useAuthForm.js";
import { required, email as emailValidator, compose } from "@/shared/utils/validation/validators.js";

const { t } = useI18n();

const props = defineProps({
    submitPath: { type: String, required: true },
    loginPath: { type: String, required: true },
    sent: { type: Boolean, default: false },
    initialErrors: { type: Object, default: () => ({}) },
    values: { type: Object, default: () => ({}) },
});

const email = ref(props.values.email ?? "");
const { errors, submitOnValid } = useAuthForm(props.initialErrors);

function handleSubmit(event) {
    submitOnValid(event, {
        email: () => compose(
            required(t("frontend.errors.email_required")),
            emailValidator(t("frontend.errors.email_invalid")),
        )(email.value),
    });
}
</script>

<template>
    <AuthCard :heading="t('frontend.forgot_password.heading')" :subtitle="t('frontend.forgot_password.subtitle')">
        <template #icon><Mail class="w-6 h-6" :stroke-width="2" /></template>
        <div v-if="sent" class="text-center">
            <div class="w-14 h-14 rounded-full bg-emerald-500/15 flex items-center justify-center mx-auto mb-6">
                <Mail class="w-7 h-7 text-emerald-400" :stroke-width="2" />
            </div>
            <p class="text-secondary text-sm leading-relaxed mb-6">{{ t('frontend.forgot_password.sent') }}</p>
            <p class="text-sm text-secondary">
                <AppLink :href="loginPath" variant="front">{{ t('frontend.forgot_password.back_login') }}</AppLink>
            </p>
        </div>

        <form
            v-else
            method="POST"
            :action="submitPath"
            class="space-y-5"
            v-on:submit.prevent="handleSubmit"
        >
            <AppInput
                v-model="email"
                name="email"
                type="email"
                :label="t('frontend.login.email')"
                :placeholder="t('frontend.login.email_placeholder')"
                :error="errors.email"
                autocomplete="email"
                autofocus
                required
            />
            <AppButton type="submit" class="w-full"><Mail class="w-4 h-4" :stroke-width="2" /> {{ t('frontend.forgot_password.submit') }}</AppButton>
        </form>

        <template v-if="!sent" #footer>
            <p class="mt-6 text-center text-sm text-secondary">
                <AppLink :href="loginPath" variant="front">{{ t('frontend.forgot_password.back_login') }}</AppLink>
            </p>
        </template>
    </AuthCard>
</template>
