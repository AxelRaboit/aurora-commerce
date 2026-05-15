<script setup>
import { useI18n } from "vue-i18n";
import { Upload, Trash2, Save } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppFileInput from "@/shared/components/form/AppFileInput.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppPasswordStrength from "@/shared/components/form/AppPasswordStrength.vue";
import AppAvatar from "@/shared/components/display/AppAvatar.vue";
import { useProfileLocale } from "@core/backend/profile/composables/useProfileLocale.js";
import { useProfileInfo } from "@core/backend/profile/composables/useProfileInfo.js";
import { useProfileMood } from "@core/backend/profile/composables/useProfileMood.js";
import { useProfilePassword } from "@core/backend/profile/composables/useProfilePassword.js";
import { useProfileDelete } from "@core/backend/profile/composables/useProfileDelete.js";
import { useProfilePhoto } from "@core/backend/profile/composables/useProfilePhoto.js";

const { t } = useI18n();

const props = defineProps({
    userName: { type: String, default: "" },
    userEmail: { type: String, default: "" },
    userPhotoUrl: { type: String, default: "" },
    userMoodMessage: { type: String, default: "" },
    moodMessageMaxLength: { type: Number, required: true },
    locale: { type: String, default: "fr" },
    updatePath: { type: String, required: true },
    passwordPath: { type: String, required: true },
    localePath: { type: String, required: true },
    moodPath: { type: String, required: true },
    photoUploadPath: { type: String, required: true },
    photoDeletePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    loginPath: { type: String, required: true },
    deleteCsrf: { type: String, default: "" },
});

const { selectedLocale, localeLoading, changeLocale } = useProfileLocale(props.localePath, props.locale);
const { infoName, infoEmail, infoLoading, infoErrors, saveInfo } = useProfileInfo(props.updatePath, props.userName, props.userEmail);
const { moodMessage, moodLoading, moodError, saveMood } = useProfileMood(props.moodPath, props.userMoodMessage, props.moodMessageMaxLength);
const { currentPassword, newPassword, confirmPassword, passwordLoading, passwordErrors, savePassword } = useProfilePassword(props.passwordPath);
const { deleteLoading, deleteAccount } = useProfileDelete(props.deletePath, props.loginPath, props.deleteCsrf);
const { photoUrl, photoLoading, onPhotoSelected, removePhoto } = useProfilePhoto(props.photoUploadPath, props.photoDeletePath, props.userPhotoUrl);
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-6">
        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('backend.profile.locale.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('backend.profile.locale.subtitle') }}</p>
            </header>
            <div>
                <AppSelect
                    v-model="selectedLocale"
                    :label="t('backend.profile.locale.field')"
                    :options="[
                        { value: 'fr', label: t('shared.locales.fr') },
                        { value: 'en', label: t('shared.locales.en') },
                    ]"
                    :disabled="localeLoading"
                    v-on:update:model-value="changeLocale"
                />
            </div>
        </div>

        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('backend.profile.photo.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('backend.profile.photo.subtitle') }}</p>
            </header>
            <div class="flex items-center gap-5">
                <AppAvatar variant="solid" :name="userName" :photo-url="photoUrl" :size="80" />
                <div class="flex flex-col gap-2">
                    <AppFileInput accept="image/jpeg,image/png,image/webp" v-on:change="onPhotoSelected">
                        <template #default="{ trigger }">
                            <div class="flex items-center gap-2 flex-wrap">
                                <AppButton variant="ghost" size="sm" :loading="photoLoading" v-on:click="trigger">
                                    <Upload class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t('backend.users.photo.upload') }}
                                </AppButton>
                                <AppButton
                                    v-if="photoUrl"
                                    variant="ghost"
                                    size="sm"
                                    :loading="photoLoading"
                                    v-on:click="removePhoto"
                                >
                                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t('backend.users.photo.remove') }}
                                </AppButton>
                            </div>
                        </template>
                    </AppFileInput>
                    <p class="text-xs text-muted">{{ t('backend.users.photo.hint') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('backend.profile.mood.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('backend.profile.mood.subtitle') }}</p>
            </header>
            <form class="space-y-3" v-on:submit.prevent="saveMood">
                <div>
                    <AppTextarea
                        v-model="moodMessage"
                        :rows="2"
                        :maxlength="moodMessageMaxLength"
                        :placeholder="t('backend.profile.mood.placeholder')"
                        :error="moodError"
                    />
                    <div v-if="!moodError" class="mt-1 flex items-center justify-between">
                        <span class="text-xs text-muted">{{ t('backend.profile.mood.hint') }}</span>
                        <span class="text-xs text-muted">{{ moodMessage.length }}/{{ moodMessageMaxLength }}</span>
                    </div>
                </div>
                <div class="pt-1">
                    <AppButton type="submit" :loading="moodLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </div>
            </form>
        </div>

        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('backend.profile.info.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('backend.profile.info.subtitle') }}</p>
            </header>
            <form class="space-y-4" v-on:submit.prevent="saveInfo">
                <AppInput
                    v-model="infoName"
                    :label="t('backend.profile.info.name')"
                    :placeholder="t('backend.profile.info.namePlaceholder')"
                    :error="infoErrors.name"
                    autocomplete="name"
                    required
                />
                <AppInput
                    v-model="infoEmail"
                    type="email"
                    :label="t('backend.profile.info.email')"
                    :placeholder="t('backend.profile.info.emailPlaceholder')"
                    :error="infoErrors.email"
                    autocomplete="email"
                    required
                />
                <div class="pt-1">
                    <AppButton type="submit" :loading="infoLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </div>
            </form>
        </div>

        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('backend.profile.password.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('backend.profile.password.subtitle') }}</p>
            </header>
            <form class="space-y-4" v-on:submit.prevent="savePassword">
                <AppInput
                    v-model="currentPassword"
                    :label="t('backend.profile.password.current')"
                    :error="passwordErrors.current_password"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    toggleable
                    required
                />
                <div>
                    <AppInput
                        v-model="newPassword"
                        :label="t('backend.profile.password.new')"
                        :error="passwordErrors.password"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        toggleable
                        required
                    />
                    <AppPasswordStrength :password="newPassword" />
                </div>
                <AppInput
                    v-model="confirmPassword"
                    :label="t('backend.profile.password.confirm')"
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

        <div class="bg-surface border border-rose-900/40 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-rose-400">{{ t('backend.profile.danger.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('backend.profile.danger.description') }}</p>
            </header>
            <AppButton variant="danger" size="md" :disabled="deleteLoading" v-on:click="deleteAccount">
                <Trash2 class="w-4 h-4" :stroke-width="2" />
                {{ t('backend.profile.danger.submit') }}
            </AppButton>
        </div>
    </div>
</template>
