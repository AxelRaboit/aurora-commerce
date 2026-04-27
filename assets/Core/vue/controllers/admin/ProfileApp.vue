<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/AppButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import PasswordStrength from "@/shared/components/PasswordStrength.vue";
import { useProfileLocale } from "@core/admin/profile/composables/useProfileLocale.js";
import { useProfileInfo } from "@core/admin/profile/composables/useProfileInfo.js";
import { useProfilePassword } from "@core/admin/profile/composables/useProfilePassword.js";
import { useProfileDelete } from "@core/admin/profile/composables/useProfileDelete.js";

const { t } = useI18n();

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
                <h2 class="text-lg font-semibold text-primary">{{ t('admin.profile.locale.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('admin.profile.locale.subtitle') }}</p>
            </header>
            <div>
                <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('admin.profile.locale.field') }}</label>
                <select
                    v-model="selectedLocale"
                    :disabled="localeLoading"
                    class="w-full bg-surface-2 text-primary rounded-lg px-3 py-2.5 border border-line focus:border-accent-500 focus:outline-none transition disabled:opacity-50"
                    v-on:change="changeLocale"
                >
                    <option value="fr">{{ t('shared.locales.fr') }}</option>
                    <option value="en">{{ t('shared.locales.en') }}</option>
                    <option value="es">{{ t('shared.locales.es') }}</option>
                    <option value="de">{{ t('shared.locales.de') }}</option>
                </select>
            </div>
        </div>

        <!-- Informations -->
        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('admin.profile.info.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('admin.profile.info.subtitle') }}</p>
            </header>
            <form class="space-y-4" v-on:submit.prevent="saveInfo">
                <AppInput
                    v-model="infoName"
                    :label="t('admin.profile.info.name')"
                    :placeholder="t('admin.profile.info.namePlaceholder')"
                    :error="infoErrors.name"
                    autocomplete="name"
                    required
                />
                <AppInput
                    v-model="infoEmail"
                    type="email"
                    :label="t('admin.profile.info.email')"
                    :placeholder="t('admin.profile.info.emailPlaceholder')"
                    :error="infoErrors.email"
                    autocomplete="email"
                    required
                />
                <div class="pt-1">
                    <AppButton type="submit" :loading="infoLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </div>
            </form>
        </div>

        <!-- Mot de passe -->
        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('admin.profile.password.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('admin.profile.password.subtitle') }}</p>
            </header>
            <form class="space-y-4" v-on:submit.prevent="savePassword">
                <AppInput
                    v-model="currentPassword"
                    :label="t('admin.profile.password.current')"
                    :error="passwordErrors.current_password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    toggleable
                    required
                />
                <div>
                    <AppInput
                        v-model="newPassword"
                        :label="t('admin.profile.password.new')"
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
                    :label="t('admin.profile.password.confirm')"
                    :error="passwordErrors.password_confirmation"
                    placeholder="••••••••"
                    autocomplete="new-password"
                    toggleable
                    required
                />
                <div class="pt-1">
                    <AppButton type="submit" :loading="passwordLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </div>
            </form>
        </div>

        <!-- Zone de danger -->
        <div class="bg-surface border border-rose-900/40 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-rose-400">{{ t('admin.profile.danger.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('admin.profile.danger.description') }}</p>
            </header>
            <AppButton variant="danger-subtle" size="md" :disabled="deleteLoading" v-on:click="deleteAccount">
                {{ t('admin.profile.danger.submit') }}
            </AppButton>
        </div>
    </div>
</template>
