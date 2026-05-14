<script setup>
import AppLink from "@/shared/components/nav/AppLink.vue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Lock, UserPlus, LogIn } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AuthCard from "@core/frontend/components/AuthCard.vue";
import { useAuthForm } from "@/shared/composables/form/useAuthForm.js";
import { required, email as emailValidator, compose } from "@/shared/utils/validation/validators.js";
import { passwordValidator } from "@/shared/utils/validation/passwordRules.js";

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

const { errors, submitOnValid } = useAuthForm(props.initialErrors);

function handleSubmit(event) {
    submitOnValid(event, {
        name: () => required(t("frontend.errors.name_required"))(name.value),
        email: () => compose(
            required(t("frontend.errors.email_required")),
            emailValidator(t("frontend.errors.email_invalid")),
        )(email.value),
        password: () => passwordValidator(t)(password.value),
    });
}
</script>

<template>
    <AuthCard
        v-if="registrationEnabled"
        :heading="t('frontend.register.heading')"
        :subtitle="t('frontend.register.subtitle')"
    >
        <template #icon><UserPlus class="w-6 h-6" :stroke-width="2" /></template>
        <form method="POST" :action="registerPath" class="space-y-5" v-on:submit.prevent="handleSubmit">
            <AppInput
                v-model="name"
                name="name"
                :label="t('frontend.register.name')"
                :placeholder="t('frontend.register.name_placeholder')"
                :error="errors.name"
                autocomplete="name"
                autofocus
                required
            />
            <AppInput
                v-model="email"
                name="email"
                type="email"
                :label="t('frontend.register.email')"
                :placeholder="t('frontend.register.email_placeholder')"
                :error="errors.email"
                autocomplete="email"
                required
            />
            <AppInput
                v-model="password"
                name="password"
                :label="t('frontend.register.password')"
                placeholder="••••••••"
                :error="errors.password"
                autocomplete="new-password"
                toggleable
                required
            />
            <AppButton type="submit" class="w-full"><UserPlus class="w-4 h-4" :stroke-width="2" /> {{ t('frontend.register.submit') }}</AppButton>
        </form>

        <template #footer>
            <p class="mt-6 text-center text-sm text-secondary">
                {{ t('frontend.register.already_account') }}
                <AppLink :href="loginPath" variant="front">{{ t('frontend.register.login_link') }}</AppLink>
            </p>
        </template>
    </AuthCard>

    <AuthCard
        v-else
        :heading="t('frontend.register.closed_title')"
        :subtitle="t('frontend.register.closed_desc')"
    >
        <template #icon><Lock class="w-6 h-6" :stroke-width="2" /></template>
        <div class="text-center">
            <AppButton :href="loginPath" variant="primary">
                <LogIn class="w-4 h-4" :stroke-width="2" />
                {{ t('frontend.login.submit') }}
            </AppButton>
        </div>
    </AuthCard>
</template>
