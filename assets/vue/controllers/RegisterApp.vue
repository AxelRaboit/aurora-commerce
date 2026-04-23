<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";
import PasswordStrength from "@/components/PasswordStrength.vue";
import { useForm } from "@/composables/useForm.js";
import { required, email as emailValidator, compose } from "@/utils/validators.js";
import { passwordValidator } from "@/utils/passwordRules.js";

const { t } = useI18n();

const props = defineProps({
    registerPath: { type: String, required: true },
    loginPath: { type: String, required: true },
    registrationEnabled: { type: Boolean, default: true },
    initialErrors: { type: Object, default: () => ({}) },
    values: { type: Object, default: () => ({}) },
});

const name = ref(props.values.name ?? "");
const email = ref(props.values.email ?? "");
const password = ref("");
const passwordConfirmation = ref("");

const { errors, validate, setErrors } = useForm();

if (Object.keys(props.initialErrors).length > 0) {
    setErrors(props.initialErrors);
}

function handleSubmit(event) {
    const isValid = validate({
        name: () => required(t("auth.register.error_name_required"))(name.value),
        email: () => compose(
            required(t("auth.register.error_email_required")),
            emailValidator(t("auth.register.error_email_invalid")),
        )(email.value),
        password: () => passwordValidator(t)(password.value),
        password_confirmation: () => {
            if (password.value && password.value !== passwordConfirmation.value) {
                return t("auth.register.error_password_mismatch");
            }
            return null;
        },
    });

    if (isValid) {
        event.target.submit();
    }
}
</script>

<template>
    <div v-if="!registrationEnabled" class="space-y-2 text-center py-4">
        <p class="text-primary font-semibold">{{ t('auth.register.closed_title') }}</p>
        <p class="text-secondary text-sm">{{ t('auth.register.closed_desc') }}</p>
        <div class="mt-6 flex flex-col gap-2 text-sm">
            <a :href="loginPath" class="text-primary underline">{{ t('auth.register.login_link') }}</a>
        </div>
    </div>

    <template v-else>
        <h2 class="text-lg font-bold text-primary mb-5">{{ t('auth.register.heading') }}</h2>

        <form method="POST" :action="registerPath" class="flex flex-col gap-4" v-on:submit.prevent="handleSubmit">
            <AppInput
                v-model="name"
                name="name"
                :label="t('auth.register.name')"
                :placeholder="t('auth.register.name_placeholder')"
                :error="errors.name"
                autocomplete="name"
                autofocus
                required
            />
            <AppInput
                v-model="email"
                name="email"
                type="email"
                :label="t('auth.register.email')"
                placeholder="you@example.com"
                :error="errors.email"
                autocomplete="email"
                required
            />
            <div>
                <AppInput
                    v-model="password"
                    name="password"
                    :label="t('auth.register.password')"
                    placeholder="••••••••"
                    :error="errors.password"
                    autocomplete="new-password"
                    toggleable
                    required
                />
                <PasswordStrength :password="password" />
            </div>
            <AppInput
                v-model="passwordConfirmation"
                name="password_confirmation"
                :label="t('auth.register.password_confirm')"
                placeholder="••••••••"
                :error="errors.password_confirmation"
                autocomplete="new-password"
                toggleable
                required
            />
            <AppButton type="submit">{{ t('auth.register.submit') }}</AppButton>
        </form>

        <div class="mt-6 flex items-center gap-4">
            <div class="flex-1 border-t border-line" />
            <span class="text-sm text-muted">{{ t('common.or') }}</span>
            <div class="flex-1 border-t border-line" />
        </div>

        <p class="mt-4 text-center text-sm text-secondary">
            {{ t('auth.register.already_account') }}
            <a :href="loginPath" class="text-primary underline">{{ t('auth.register.login_link') }}</a>
        </p>
    </template>
</template>
