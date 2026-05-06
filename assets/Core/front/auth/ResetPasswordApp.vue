<script setup>
import AppLink from "@/shared/components/nav/AppLink.vue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { KeyRound } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AuthCard from "@/front/components/AuthCard.vue";
import { useAuthForm } from "@/shared/composables/form/useAuthForm.js";
import { passwordValidator } from "@/shared/utils/validation/passwordRules.js";

const { t } = useI18n();

const props = defineProps({
    submitPath: { type: String, required: true },
    forgotPath: { type: String, required: true },
    invalid: { type: Boolean, default: false },
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
            : t("frontend.errors.passwords_mismatch"),
    });
}
</script>

<template>
    <AuthCard
        :heading="t('frontend.reset_password.heading')"
        :subtitle="invalid ? '' : t('frontend.reset_password.subtitle')"
    >
        <template #icon><KeyRound class="w-6 h-6" :stroke-width="2" /></template>
        <template v-if="invalid">
            <div class="rounded-lg bg-danger-soft border border-danger/30 px-4 py-4 text-sm text-danger mb-6">
                {{ t('frontend.reset_password.invalid_link') }}
            </div>
            <p class="text-center">
                <AppLink :href="forgotPath" variant="front-accent" class="text-sm">{{ t('frontend.forgot_password.submit') }}</AppLink>
            </p>
        </template>

        <form
            v-else
            method="POST"
            :action="submitPath"
            class="space-y-5"
            v-on:submit.prevent="handleSubmit"
        >
            <AppInput
                v-model="password"
                name="password"
                :label="t('frontend.reset_password.new_password')"
                placeholder="••••••••"
                :error="errors.password"
                autocomplete="new-password"
                toggleable
                autofocus
                required
            />
            <AppInput
                v-model="passwordConfirmation"
                name="password_confirmation"
                :label="t('frontend.reset_password.confirm_password')"
                placeholder="••••••••"
                :error="errors.password_confirmation"
                autocomplete="new-password"
                toggleable
                required
            />
            <AppButton type="submit" class="w-full"><KeyRound class="w-4 h-4" :stroke-width="2" /> {{ t('frontend.reset_password.submit') }}</AppButton>
        </form>
    </AuthCard>
</template>
