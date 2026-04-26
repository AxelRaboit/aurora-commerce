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
    submitPath: { type: String, required: true },
    loginPath: { type: String, required: true },
    initialErrors: { type: Object, default: () => ({}) },
    values: { type: Object, default: () => ({}) },
});

const name = ref(props.values.name ?? "");
const email = ref(props.values.email ?? "");
const message = ref(props.values.message ?? "");
const { errors, submitOnValid } = useAuthForm(props.initialErrors);

function handleSubmit(event) {
    submitOnValid(event, {
        email: () => compose(
            required(t("auth.register.error_email_required")),
            emailValidator(t("auth.register.error_email_invalid")),
        )(email.value),
    });
}
</script>

<template>
    <h2 class="text-lg font-bold text-primary mb-2">{{ t('auth.access_request.title') }}</h2>
    <p class="text-sm text-secondary mb-5">{{ t('auth.access_request.description') }}</p>

    <form method="POST" :action="submitPath" class="flex flex-col gap-4" v-on:submit.prevent="handleSubmit">
        <AppInput
            v-model="name"
            name="name"
            :label="t('auth.access_request.name')"
            :placeholder="t('auth.access_request.name_placeholder')"
            :error="errors.name"
            autocomplete="name"
            autofocus
        />

        <AppInput
            v-model="email"
            name="email"
            type="email"
            :label="t('auth.access_request.email')"
            :placeholder="t('auth.login.email_placeholder')"
            :error="errors.email"
            autocomplete="email"
            required
        />

        <div>
            <label for="access-message" class="block text-sm font-medium text-secondary mb-1 uppercase">{{ t('auth.access_request.message') }}</label>
            <textarea
                id="access-message"
                v-model="message"
                name="message"
                rows="4"
                :placeholder="t('auth.access_request.message_placeholder')"
                class="w-full px-3 py-2 border border-line rounded-md bg-surface-2 text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition resize-none"
            />
        </div>

        <AppButton type="submit">{{ t('auth.access_request.submit') }}</AppButton>
    </form>

    <div class="mt-6 flex items-center gap-4">
        <div class="flex-1 border-t border-line" />
        <span class="text-sm text-secondary">{{ t('common.or') }}</span>
        <div class="flex-1 border-t border-line" />
    </div>

    <p class="mt-4 text-center text-sm text-secondary">
        {{ t('auth.access_request.already_account') }}
        <AppLink :href="loginPath">{{ t('auth.access_request.login_link') }}</AppLink>
    </p>
</template>
