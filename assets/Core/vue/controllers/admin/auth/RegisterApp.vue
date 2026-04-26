<script setup>
import AppLink from "@/shared/components/AppLink.vue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/AppButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import PasswordStrength from "@/shared/components/PasswordStrength.vue";
import { useAuthForm } from "@/shared/composables/useAuthForm.js";
import { required, email as emailValidator, compose } from "@/shared/utils/validators.js";
import { passwordValidator } from "@/shared/utils/passwordRules.js";

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

const { errors, submitOnValid } = useAuthForm(props.initialErrors);

function handleSubmit(event) {
    submitOnValid(event, {
        name: () => required(t("admin.auth.register.error_name_required"))(name.value),
        email: () => compose(
            required(t("admin.auth.register.error_email_required")),
            emailValidator(t("admin.auth.register.error_email_invalid")),
        )(email.value),
        password: () => passwordValidator(t)(password.value),
        password_confirmation: () => password.value === passwordConfirmation.value
            ? null
            : t("admin.auth.register.error_password_mismatch"),
    });
}
</script>

<template>
    <div v-if="!registrationEnabled" class="space-y-2 text-center py-4">
        <p class="text-primary font-semibold">{{ t('admin.auth.register.closed_title') }}</p>
        <p class="text-secondary text-sm">{{ t('admin.auth.register.closed_desc') }}</p>
        <div class="mt-6 flex flex-col gap-2 text-sm">
            <AppLink :href="loginPath">{{ t('admin.auth.register.login_link') }}</AppLink>
        </div>
    </div>

    <template v-else>
        <h2 class="text-lg font-bold text-primary mb-5">{{ t('admin.auth.register.heading') }}</h2>

        <form method="POST" :action="registerPath" class="flex flex-col gap-4" v-on:submit.prevent="handleSubmit">
            <AppInput
                v-model="name"
                name="name"
                :label="t('admin.auth.register.name')"
                :placeholder="t('admin.auth.register.name_placeholder')"
                :error="errors.name"
                autocomplete="name"
                autofocus
                required
            />
            <AppInput
                v-model="email"
                name="email"
                type="email"
                :label="t('admin.auth.register.email')"
                placeholder="you@example.com"
                :error="errors.email"
                autocomplete="email"
                required
            />
            <div>
                <AppInput
                    v-model="password"
                    name="password"
                    :label="t('admin.auth.register.password')"
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
                :label="t('admin.auth.register.password_confirm')"
                placeholder="••••••••"
                :error="errors.password_confirmation"
                autocomplete="new-password"
                toggleable
                required
            />
            <AppButton type="submit">{{ t('admin.auth.register.submit') }}</AppButton>
        </form>

        <div class="mt-6 flex items-center gap-4">
            <div class="flex-1 border-t border-line" />
            <span class="text-sm text-muted">{{ t('shared.common.or') }}</span>
            <div class="flex-1 border-t border-line" />
        </div>

        <p class="mt-4 text-center text-sm text-secondary">
            {{ t('admin.auth.register.already_account') }}
            <AppLink :href="loginPath">{{ t('admin.auth.register.login_link') }}</AppLink>
        </p>
    </template>
</template>
