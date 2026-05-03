<script setup>
import AppLink from "@/shared/components/nav/AppLink.vue";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Lock, UserPlus, LogIn } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AuthCard from "@/front/components/AuthCard.vue";
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
        name: () => required(t("front.errors.name_required"))(name.value),
        email: () => compose(
            required(t("front.errors.email_required")),
            emailValidator(t("front.errors.email_invalid")),
        )(email.value),
        password: () => passwordValidator(t)(password.value),
    });
}
</script>

<template>
    <AuthCard
        v-if="registrationEnabled"
        :heading="t('front.register.heading')"
        :subtitle="t('front.register.subtitle')"
    >
        <form method="POST" :action="registerPath" class="space-y-5" v-on:submit.prevent="handleSubmit">
            <AppInput
                v-model="name"
                name="name"
                :label="t('front.register.name')"
                :placeholder="t('front.register.name_placeholder')"
                :error="errors.name"
                autocomplete="name"
                autofocus
                required
            />
            <AppInput
                v-model="email"
                name="email"
                type="email"
                :label="t('front.register.email')"
                :placeholder="t('front.register.email_placeholder')"
                :error="errors.email"
                autocomplete="email"
                required
            />
            <AppInput
                v-model="password"
                name="password"
                :label="t('front.register.password')"
                placeholder="••••••••"
                :error="errors.password"
                autocomplete="new-password"
                toggleable
                required
            />
            <AppButton type="submit" class="w-full"><UserPlus class="w-4 h-4" :stroke-width="2" /> {{ t('front.register.submit') }}</AppButton>
        </form>

        <template #footer>
            <p class="mt-6 text-center text-sm text-secondary">
                {{ t('front.register.already_account') }}
                <AppLink :href="loginPath" variant="front">{{ t('front.register.login_link') }}</AppLink>
            </p>
        </template>
    </AuthCard>

    <AuthCard v-else>
        <div class="text-center py-8">
            <div class="w-14 h-14 rounded-full bg-rose-500/15 flex items-center justify-center mx-auto mb-6">
                <Lock class="w-7 h-7 text-rose-400" :stroke-width="2" />
            </div>
            <h1 class="text-2xl font-bold text-primary mb-3">{{ t('front.register.closed_title') }}</h1>
            <p class="text-secondary text-sm leading-relaxed mb-8">{{ t('front.register.closed_desc') }}</p>
            <AppButton :href="loginPath" variant="primary">
                <LogIn class="w-4 h-4" :stroke-width="2" />
                {{ t('front.login.submit') }}
            </AppButton>
        </div>
    </AuthCard>
</template>
