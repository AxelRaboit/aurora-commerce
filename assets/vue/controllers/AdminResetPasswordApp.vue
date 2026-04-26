<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";
import PasswordStrength from "@/components/PasswordStrength.vue";
import { useAuthForm } from "@/composables/useAuthForm.js";
import { passwordValidator } from "@/utils/passwordRules.js";

const { t } = useI18n();

const props = defineProps({
    submitPath: { type: String, required: true },
    initialErrors: { type: Object, default: () => ({}) },
});

const password = ref("");
const passwordConfirmation = ref("");
const { errors, submitOnValid } = useAuthForm(props.initialErrors);

function handleSubmit(event) {
    submitOnValid(event, {
        password: () => passwordValidator(t)(password.value),
        password_confirmation: () => password.value === passwordConfirmation.value
            ? null
            : t("auth.register.error_password_mismatch"),
    });
}
</script>

<template>
    <p class="mb-4 text-sm text-secondary">{{ t('auth.reset_password.instructions') }}</p>

    <form method="POST" :action="submitPath" class="space-y-4" v-on:submit.prevent="handleSubmit">
        <div>
            <AppInput
                v-model="password"
                name="password"
                :label="t('auth.register.password')"
                placeholder="••••••••"
                :error="errors.password"
                autocomplete="new-password"
                toggleable
                autofocus
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

        <div class="flex justify-end">
            <AppButton type="submit">{{ t('auth.reset_password.submit') }}</AppButton>
        </div>
    </form>
</template>
