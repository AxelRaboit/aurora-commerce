<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";
import PasswordStrength from "@/components/PasswordStrength.vue";
import { useProfileLocale } from "./composables/useProfileLocale.js";
import { useProfileInfo } from "./composables/useProfileInfo.js";
import { useProfilePassword } from "./composables/useProfilePassword.js";
import { useProfileDelete } from "./composables/useProfileDelete.js";

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

const { selectedLocale, localeLoading, changeLocale } = useProfileLocale(props.localePath, props.locale);
const { infoName, infoEmail, infoLoading, infoErrors, saveInfo } = useProfileInfo(props.updatePath, props.userName, props.userEmail);
const { currentPassword, newPassword, confirmPassword, passwordLoading, passwordErrors, savePassword } = useProfilePassword(props.passwordPath);
const { deleteLoading, deleteAccount } = useProfileDelete(props.deletePath, props.loginPath, props.deleteCsrf);
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
