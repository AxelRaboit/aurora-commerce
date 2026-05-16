<script setup>
import AppLink from "@/shared/components/nav/AppLink.vue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { LogIn } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AuthCard from "@core/frontend/components/AuthCard.vue";
import { useAuthForm } from "@/shared/composables/form/useAuthForm.js";
import { required, email as emailValidator, compose } from "@/shared/utils/validation/validators.js";

const { t } = useI18n();

const props = defineProps({
    checkPath: { type: String, required: true },
    csrfToken: { type: String, required: true },
    locale: { type: String, required: true },
    forgotPath: { type: String, required: true },
    registerPath: { type: String, default: null },
    registrationEnabled: { type: Boolean, default: false },
    lastEmail: { type: String, default: "" },
    errorMessage: { type: String, default: "" },
    resetSuccess: { type: Boolean, default: false },
});

const email = ref(props.lastEmail);
const password = ref("");
const { errors, submitOnValid } = useAuthForm();

function handleSubmit(event) {
    submitOnValid(event, {
        email: () => compose(
            required(t("frontend.errors.email_required")),
            emailValidator(t("frontend.errors.email_invalid")),
        )(email.value),
        password: () => required(t("frontend.errors.password_required"))(password.value),
    });
}
</script>

<template>
    <AuthCard :heading="t('frontend.login.heading')" :subtitle="t('frontend.login.subtitle')">
        <template #icon><LogIn class="w-6 h-6" :stroke-width="2" /></template>
        <template #banner>
            <div v-if="resetSuccess" class="mb-6 rounded-lg bg-success-soft border border-success/30 px-4 py-3 text-sm text-success">
                {{ t('frontend.reset_password.success') }}
            </div>
            <div v-if="errorMessage" class="mb-6 rounded-lg bg-danger-soft border border-danger/30 px-4 py-3 text-sm text-danger">
                {{ errorMessage }}
            </div>
        </template>

        <form method="POST" :action="checkPath" class="space-y-5" v-on:submit.prevent="handleSubmit">
            <input type="hidden" name="_csrf_token" :value="csrfToken">
            <input type="hidden" name="_locale" :value="locale">

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

            <div>
                <AppInput
                    v-model="password"
                    name="password"
                    :label="t('frontend.login.password')"
                    placeholder="••••••••"
                    :error="errors.password"
                    autocomplete="current-password"
                    toggleable
                    required
                />
                <div class="mt-1 text-right">
                    <AppLink :href="forgotPath" variant="front-accent" class="text-xs">{{ t('frontend.login.forgot_password') }}</AppLink>
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-secondary cursor-pointer">
                <input type="checkbox" name="_remember_me" class="rounded border-line text-accent focus:ring-accent">
                {{ t('frontend.login.remember_me') }}
            </label>

            <AppButton type="submit" class="w-full"><LogIn class="w-4 h-4" :stroke-width="2" /> {{ t('frontend.login.submit') }}</AppButton>
        </form>

        <template #footer>
            <p v-if="registrationEnabled && registerPath" class="mt-6 text-center text-sm text-secondary">
                {{ t('frontend.login.no_account') }}
                <AppLink :href="registerPath" variant="front">{{ t('frontend.login.register_link') }}</AppLink>
            </p>
        </template>
    </AuthCard>
</template>
