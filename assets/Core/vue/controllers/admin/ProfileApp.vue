<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Upload, Trash2, Save } from "lucide-vue-next";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppFileInput from "@/shared/components/form/AppFileInput.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import PasswordStrength from "@/shared/components/form/PasswordStrength.vue";
import AppAvatar from "@/shared/components/display/AppAvatar.vue";
import { useProfileLocale } from "@core/admin/profile/composables/useProfileLocale.js";
import { useProfileInfo } from "@core/admin/profile/composables/useProfileInfo.js";
import { useProfileMood } from "@core/admin/profile/composables/useProfileMood.js";
import { useProfilePassword } from "@core/admin/profile/composables/useProfilePassword.js";
import { useProfileDelete } from "@core/admin/profile/composables/useProfileDelete.js";

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

const photoUrl = ref(props.userPhotoUrl);
const photoLoading = ref(false);

async function onPhotoSelected(file) {
    if (!file) return;
    photoLoading.value = true;
    try {
        const formData = new FormData();
        formData.append("photo", file);
        const response = await fetch(props.photoUploadPath, { method: HttpMethod.Post, body: formData });
        const data = await response.json();
        if (!data.success) {
            toast.error(t(data.errors?.photo ?? data.error ?? "shared.common.error"));
            return;
        }
        photoUrl.value = data.profilePhotoUrl ?? "";
        toast.success(t("admin.users.photo.uploaded"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        photoLoading.value = false;
    }
}

async function removePhoto() {
    photoLoading.value = true;
    try {
        const response = await fetch(props.photoDeletePath, { method: HttpMethod.Post });
        const data = await response.json();
        if (!data.success) {
            toast.error(t(data.error ?? "shared.common.error"));
            return;
        }
        photoUrl.value = "";
        toast.success(t("admin.users.photo.removed"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        photoLoading.value = false;
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-6">
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

        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('admin.profile.photo.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('admin.profile.photo.subtitle') }}</p>
            </header>
            <div class="flex items-center gap-5">
                <AppAvatar variant="solid" :name="userName" :photo-url="photoUrl" :size="80" />
                <div class="flex flex-col gap-2">
                    <AppFileInput accept="image/jpeg,image/png,image/webp" v-on:change="onPhotoSelected">
                        <template #default="{ trigger }">
                            <div class="flex items-center gap-2 flex-wrap">
                                <AppButton variant="ghost" size="sm" :loading="photoLoading" v-on:click="trigger">
                                    <Upload class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t('admin.users.photo.upload') }}
                                </AppButton>
                                <AppButton
                                    v-if="photoUrl"
                                    variant="ghost"
                                    size="sm"
                                    :loading="photoLoading"
                                    v-on:click="removePhoto"
                                >
                                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t('admin.users.photo.remove') }}
                                </AppButton>
                            </div>
                        </template>
                    </AppFileInput>
                    <p class="text-xs text-muted">{{ t('admin.users.photo.hint') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-surface border border-line/60 rounded-2xl p-6 shadow-sm">
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-primary">{{ t('admin.profile.mood.title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ t('admin.profile.mood.subtitle') }}</p>
            </header>
            <form class="space-y-3" v-on:submit.prevent="saveMood">
                <div>
                    <AppTextarea
                        v-model="moodMessage"
                        :rows="2"
                        :maxlength="moodMessageMaxLength"
                        :placeholder="t('admin.profile.mood.placeholder')"
                        :error="moodError"
                    />
                    <div v-if="!moodError" class="mt-1 flex items-center justify-between">
                        <span class="text-xs text-muted">{{ t('admin.profile.mood.hint') }}</span>
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
