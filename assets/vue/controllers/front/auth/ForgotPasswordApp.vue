<script setup>
import AppLink from "@/shared/components/AppLink.vue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Mail } from "lucide-vue-next";
import AppButton from "@/shared/components/AppButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AuthCard from "@/front/components/AuthCard.vue";
import { useAuthForm } from "@/shared/composables/useAuthForm.js";
import { required, email as emailValidator, compose } from "@/shared/utils/validators.js";

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
            required(t("front.errors.email_required")),
            emailValidator(t("front.errors.email_invalid")),
        )(email.value),
    });
}
</script>

<template>
    <AuthCard :heading="t('front.forgot_password.heading')" :subtitle="t('front.forgot_password.subtitle')">
        <div v-if="sent" class="text-center">
            <div class="w-14 h-14 rounded-full bg-emerald-500/15 flex items-center justify-center mx-auto mb-6">
                <Mail class="w-7 h-7 text-emerald-400" :stroke-width="2" />
            </div>
            <p class="text-secondary text-sm leading-relaxed mb-6">{{ t('front.forgot_password.sent') }}</p>
            <p class="text-sm text-secondary">
                <AppLink :href="loginPath" variant="front">{{ t('front.forgot_password.back_login') }}</AppLink>
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
                :label="t('front.login.email')"
                :placeholder="t('front.login.email_placeholder')"
                :error="errors.email"
                autocomplete="email"
                autofocus
                required
            />
            <AppButton type="submit" class="w-full">{{ t('front.forgot_password.submit') }}</AppButton>
        </form>

        <template v-if="!sent" #footer>
            <p class="mt-6 text-center text-sm text-secondary">
                <AppLink :href="loginPath" variant="front">{{ t('front.forgot_password.back_login') }}</AppLink>
            </p>
        </template>
    </AuthCard>
</template>
