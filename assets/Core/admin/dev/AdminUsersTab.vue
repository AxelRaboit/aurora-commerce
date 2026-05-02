<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppAvatar from "@/shared/components/display/AppAvatar.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { Plus, Save } from "lucide-vue-next";
import { useAdminUsers } from "@core/admin/dev/composables/useAdminUsers.js";
import AdminUserBadges from "@core/admin/dev/AdminUserBadges.vue";
import AdminUserActions from "@core/admin/dev/AdminUserActions.vue";

const LOCALE_OPTIONS = [
    { value: "fr", label: "Français" },
    { value: "en", label: "English" },
    { value: "es", label: "Español" },
    { value: "de", label: "Deutsch" },
];

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const props = defineProps({
    usersPath: { type: String, required: true },
    userCreatePath: { type: String, required: true },
    userUpdatePath: { type: String, required: true },
    userToggleRolePath: { type: String, required: true },
    userDeletePath: { type: String, required: true },
    impersonatePath: { type: String, required: true },
    csrfToken: { type: String, default: "" },
    initialData: { type: Object, default: null },
    initialSearch: { type: String, default: "" },
});

const users = useAdminUsers(
    props.usersPath,
    props.userCreatePath,
    props.userUpdatePath,
    props.userToggleRolePath,
    props.userDeletePath,
    props.impersonatePath,
    props.csrfToken,
    props.initialData,
    props.initialSearch,
);

onMounted(() => {
    if (!users.parsedUsers.value.items?.length) users.load();
});
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="users.searchInput.value"
                :placeholder="t('admin.users.searchPlaceholder')"
                v-on:search="users.performSearch"
            />
            <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="users.openCreate">
                <Plus class="w-4 h-4" />
                {{ t('admin.users.add') }}
            </AppButton>
        </div>

        <div class="sm:hidden space-y-3">
            <p v-if="!users.parsedUsers.value.items?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.users.empty') }}</p>
            <div v-for="user in users.parsedUsers.value.items" :key="user.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <AppAvatar :name="user.name" :email="user.email" size="lg" />
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ user.name }}</p>
                            <p class="text-xs text-secondary truncate">{{ user.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <AdminUserBadges :user="user" />
                    </div>
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-line">
                    <p class="text-xs text-muted">{{ formatDateShort(user.createdAt) }}</p>
                    <div class="flex items-center gap-1">
                        <AdminUserActions
                            :user="user"
                            :impersonate-path="users.impersonatePath"
                            v-on:edit="users.openEdit"
                            v-on:toggle-role="users.confirmToggleRole"
                            v-on:delete="users.confirmDelete"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary">{{ t('admin.users.name') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary">{{ t('admin.users.email') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ t('admin.users.role') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t('admin.users.locale') }}</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t('admin.users.created') }}</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-primary">{{ t('admin.users.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="user in users.parsedUsers.value.items" :key="user.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <AppAvatar :name="user.name" :email="user.email" size="md" />
                                <p class="font-medium text-primary inline-flex items-center gap-1.5">
                                    {{ user.name }}
                                    <AppBadge v-if="user.isCurrent" color="accent">{{ t('admin.users.you') }}</AppBadge>
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-secondary">{{ user.email }}</td>
                        <td class="px-6 py-3 hidden md:table-cell">
                            <AppBadge :color="user.isDevRole ? 'accent' : 'gray'">
                                {{ user.isDevRole ? t('admin.users.role_dev') : t('admin.users.role_user') }}
                            </AppBadge>
                        </td>
                        <td class="px-6 py-3 hidden lg:table-cell">
                            <AppBadge color="gray" class="uppercase">{{ user.locale }}</AppBadge>
                        </td>
                        <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(user.createdAt) }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <AdminUserActions
                                    :user="user"
                                    :impersonate-path="users.impersonatePath"
                                    v-on:edit="users.openEdit"
                                    v-on:toggle-role="users.confirmToggleRole"
                                    v-on:delete="users.confirmDelete"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!users.parsedUsers.value.items?.length">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.users.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppModal :show="!!users.pendingDelete.value" max-width="sm" v-on:close="users.pendingDelete.value = null">
            <p class="text-sm text-primary">{{ t('admin.users.deleteConfirm', { name: users.pendingDelete.value?.name }) }}</p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="users.pendingDelete.value = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="users.doDelete">{{ t('shared.common.delete') }}</AppButton>
            </div>
        </AppModal>

        <AppModal :show="users.showCreateModal.value" max-width="md" v-on:close="users.showCreateModal.value = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.users.add') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="users.submitCreate">
                <AppInput
                    v-model="users.newUser.value.name"
                    :label="t('admin.users.name')"
                    :error="users.createErrors.value.name"
                    autocomplete="name"
                    required
                />
                <AppInput
                    v-model="users.newUser.value.email"
                    type="email"
                    :label="t('admin.users.email')"
                    :error="users.createErrors.value.email"
                    autocomplete="email"
                    required
                />
                <AppInput
                    v-model="users.newUser.value.password"
                    :label="t('admin.users.password')"
                    :error="users.createErrors.value.password"
                    autocomplete="new-password"
                    toggleable
                    required
                />
                <AppSelect v-model="users.newUser.value.locale" :label="t('admin.users.locale')">
                    <option v-for="option in LOCALE_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                </AppSelect>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="users.showCreateModal.value = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="users.createLoading.value">{{ t('shared.common.create') }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="users.showEditModal.value" max-width="md" v-on:close="users.closeEdit">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.users.edit_title', { name: users.editingUser.value?.name ?? '' }) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="users.submitEdit">
                <AppInput
                    v-model="users.editUserForm.value.name"
                    :label="t('admin.users.name')"
                    :error="users.editErrors.value.name"
                    autocomplete="name"
                    required
                />
                <AppInput
                    v-model="users.editUserForm.value.email"
                    type="email"
                    :label="t('admin.users.email')"
                    :error="users.editErrors.value.email"
                    autocomplete="email"
                    required
                />
                <AppInput
                    v-model="users.editUserForm.value.password"
                    :label="t('admin.users.password_optional')"
                    :error="users.editErrors.value.password"
                    autocomplete="new-password"
                    toggleable
                />
                <AppSelect v-model="users.editUserForm.value.locale" :label="t('admin.users.locale')">
                    <option v-for="option in LOCALE_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                </AppSelect>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="users.closeEdit">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="users.editLoading.value"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="!!users.pendingToggleRole.value" max-width="sm" v-on:close="users.pendingToggleRole.value = null">
            <p class="text-sm text-primary">
                {{ users.pendingToggleRole.value?.isDevRole
                    ? t('admin.users.revokeDevConfirm', { name: users.pendingToggleRole.value?.name })
                    : t('admin.users.grantDevConfirm', { name: users.pendingToggleRole.value?.name }) }}
            </p>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="users.pendingToggleRole.value = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" size="md" v-on:click="users.doToggleRole">{{ t('shared.common.confirm') }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
