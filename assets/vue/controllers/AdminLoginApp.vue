<script setup>
import AppLink from "@/components/AppLink.vue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";
import { useAuthForm } from "@/composables/useAuthForm.js";
import { required, email as emailValidator, compose } from "@/utils/validators.js";

const { t } = useI18n();

const props = defineProps({
    checkPath: { type: String, required: true },
    csrfToken: { type: String, required: true },
    forgotPath: { type: String, required: true },
    registerPath: { type: String, required: true },
    accessRequestPath: { type: String, required: true },
    registrationEnabled: { type: Boolean, default: false },
    lastUsername: { type: String, default: "" },
    errorMessage: { type: String, default: "" },
});

const email = ref(props.lastUsername);
const password = ref("");
const { errors, submitOnValid } = useAuthForm();

function handleSubmit(event) {
    submitOnValid(event, {
        email: () => compose(
            required(t("auth.register.error_email_required")),
            emailValidator(t("auth.register.error_email_invalid")),
        )(email.value),
        password: () => required(t("auth.invitation.errors.password_required"))(password.value),
    });
}
</script>

<template>
    <div v-if="errorMessage" class="mb-4 text-sm font-medium text-red-500">{{ errorMessage }}</div>

    <form method="POST" :action="checkPath" class="flex flex-col gap-4" v-on:submit.prevent="handleSubmit">
        <input type="hidden" name="_csrf_token" :value="csrfToken">

        <AppInput
            v-model="email"
            name="email"
            type="email"
            :label="t('auth.login.email')"
            :placeholder="t('auth.login.email_placeholder')"
            :error="errors.email"
            autocomplete="email"
            autofocus
            required
        />

        <AppInput
            v-model="password"
            name="password"
            :label="t('auth.login.password')"
            placeholder="••••••••"
            :error="errors.password"
            autocomplete="current-password"
            toggleable
            required
        />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="_remember_me" value="1" class="w-4 h-4 rounded border-line bg-surface-2 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0">
                <span class="text-sm text-secondary">{{ t('auth.login.remember') }}</span>
            </label>
            <AppLink :href="forgotPath" class="text-sm">{{ t('auth.login.forgot') }}</AppLink>
        </div>

        <AppButton type="submit">{{ t('auth.login.submit') }}</AppButton>
    </form>

    <div class="mt-6 flex items-center gap-4">
        <div class="flex-1 border-t border-line" />
        <span class="text-sm text-secondary">{{ t('common.or') }}</span>
        <div class="flex-1 border-t border-line" />
    </div>

    <div class="mt-4 flex flex-col gap-2 text-center text-sm">
        <AppLink v-if="registrationEnabled" :href="registerPath">{{ t('auth.login.no_account') }}</AppLink>
        <AppLink :href="accessRequestPath">{{ t('auth.login.request_access') }}</AppLink>
    </div>
</template>
