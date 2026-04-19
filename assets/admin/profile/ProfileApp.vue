<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";
import PasswordStrength from "@/components/PasswordStrength.vue";
import { useForm } from "@/composables/useForm.js";
import { required, email, compose } from "@/utils/validators.js";
import { passwordValidator } from "@/utils/passwordRules.js";

const { t: translate } = useI18n();

const props = defineProps({
    userName: { type: String, default: "" },
    userEmail: { type: String, default: "" },
    locale: { type: String, default: "fr" },
    updatePath: { type: String, required: true },
    passwordPath: { type: String, required: true },
    localePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    loginPath: { type: String, required: true },
    deleteCsrf: { type: String, default: "" },
});

// ── Locale ────────────────────────────────────────────────────────────────────

const selectedLocale = ref(props.locale);
const localeLoading = ref(false);

async function changeLocale() {
    if (selectedLocale.value === props.locale) return;
    localeLoading.value = true;
    try {
        const response = await fetch(props.localePath, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ locale: selectedLocale.value }),
        });
        if (!response.ok) {
            selectedLocale.value = props.locale;
            return;
        }
        window.location.reload();
    } catch {
        selectedLocale.value = props.locale;
    } finally {
        localeLoading.value = false;
    }
}

// ── Profile info ──────────────────────────────────────────────────────────────

const infoName = ref(props.userName);
const infoEmail = ref(props.userEmail);
const infoLoading = ref(false);
const { errors: infoErrors, validate: validateInfo, setErrors: setInfoErrors, clearErrors: clearInfoErrors } = useForm();

async function saveInfo() {
    const isValid = validateInfo({
        name: () => required(translate("profile.errors.name_required"))(infoName.value),
        email: () => compose(
            required(translate("profile.errors.email_invalid")),
            email(translate("profile.errors.email_invalid")),
        )(infoEmail.value),
    });

    if (!isValid) return;

    infoLoading.value = true;
    try {
        const response = await fetch(props.updatePath, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ name: infoName.value, email: infoEmail.value }),
        });
        const data = await response.json();
        if (data.success) {
            clearInfoErrors();
            toast.success(translate("profile.info.saved"));
        } else {
            setInfoErrors(data.errors ?? {});
        }
    } finally {
        infoLoading.value = false;
    }
}

// ── Password ──────────────────────────────────────────────────────────────────

const currentPassword = ref("");
const newPassword = ref("");
const confirmPassword = ref("");
const passwordLoading = ref(false);
const { errors: passwordErrors, validate: validatePassword, setErrors: setPasswordErrors, clearErrors: clearPasswordErrors } = useForm();

async function savePassword() {
    const isValid = validatePassword({
        current_password: () => required(translate("profile.errors.current_password_invalid"))(currentPassword.value),
        password: () => passwordValidator(translate)(newPassword.value),
        password_confirmation: () => {
            if (newPassword.value && newPassword.value !== confirmPassword.value) return translate("profile.errors.password_mismatch");
            return null;
        },
    });

    if (!isValid) return;

    passwordLoading.value = true;
    try {
        const response = await fetch(props.passwordPath, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                current_password: currentPassword.value,
                password: newPassword.value,
                password_confirmation: confirmPassword.value,
            }),
        });
        const data = await response.json();
        if (data.success) {
            clearPasswordErrors();
            toast.success(translate("profile.password.saved"));
            currentPassword.value = "";
            newPassword.value = "";
            confirmPassword.value = "";
        } else {
            setPasswordErrors(data.errors ?? {});
        }
    } finally {
        passwordLoading.value = false;
    }
}

// ── Delete account ────────────────────────────────────────────────────────────

const deleteLoading = ref(false);

async function deleteAccount() {
    if (!confirm(translate("profile.danger.confirm"))) return;
    deleteLoading.value = true;
    try {
        const response = await fetch(props.deletePath, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ _token: props.deleteCsrf }),
        });
        const data = await response.json();
        if (data.success) {
            window.location.href = props.loginPath;
        }
    } finally {
        deleteLoading.value = false;
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-6">
        <!-- Langue -->
        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ translate('profile.locale.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ translate('profile.locale.subtitle') }}</p>
            </header>
            <div>
                <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ translate('profile.locale.field') }}</label>
                <select
                    v-model="selectedLocale"
                    :disabled="localeLoading"
                    class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border border-line focus:border-indigo-500 focus:outline-none transition disabled:opacity-50"
                    v-on:change="changeLocale"
                >
                    <option value="fr">{{ translate('locales.fr') }}</option>
                    <option value="en">{{ translate('locales.en') }}</option>
                    <option value="es">{{ translate('locales.es') }}</option>
                    <option value="de">{{ translate('locales.de') }}</option>
                </select>
            </div>
        </div>

        <!-- Informations -->
        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ translate('profile.info.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ translate('profile.info.subtitle') }}</p>
            </header>
            <form class="space-y-4" v-on:submit.prevent="saveInfo">
                <AppInput
                    v-model="infoName"
                    :label="translate('profile.info.name')"
                    :placeholder="translate('profile.info.namePlaceholder')"
                    :error="infoErrors.name"
                    autocomplete="name"
                    required
                />
                <AppInput
                    v-model="infoEmail"
                    type="email"
                    :label="translate('profile.info.email')"
                    :placeholder="translate('profile.info.emailPlaceholder')"
                    :error="infoErrors.email"
                    autocomplete="email"
                    required
                />
                <div class="pt-1">
                    <AppButton type="submit" :loading="infoLoading">{{ translate('common.save') }}</AppButton>
                </div>
            </form>
        </div>

        <!-- Mot de passe -->
        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ translate('profile.password.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ translate('profile.password.subtitle') }}</p>
            </header>
            <form class="space-y-4" v-on:submit.prevent="savePassword">
                <AppInput
                    v-model="currentPassword"
                    :label="translate('profile.password.current')"
                    :error="passwordErrors.current_password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    toggleable
                    required
                />
                <div>
                    <AppInput
                        v-model="newPassword"
                        :label="translate('profile.password.new')"
                        :error="passwordErrors.password"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        toggleable
                        required
                    />
                    <PasswordStrength :password="newPassword" />
                </div>
                <AppInput
                    v-model="confirmPassword"
                    :label="translate('profile.password.confirm')"
                    :error="passwordErrors.password_confirmation"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    toggleable
                    required
                />
                <div class="pt-1">
                    <AppButton type="submit" :loading="passwordLoading">{{ translate('common.save') }}</AppButton>
                </div>
            </form>
        </div>

        <!-- Zone de danger -->
        <div class="bg-surface border border-rose-900/40 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-rose-400">{{ translate('profile.danger.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ translate('profile.danger.description') }}</p>
            </header>
            <button
                type="button"
                :disabled="deleteLoading"
                class="px-4 py-2.5 rounded-lg text-sm font-medium bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 border border-rose-900/40 transition-colors disabled:opacity-50"
                v-on:click="deleteAccount"
            >
                {{ translate('profile.danger.submit') }}
            </button>
        </div>
    </div>
</template>
