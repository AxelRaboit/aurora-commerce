<script setup>
import { computed, ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import AppInput from "@/shared/components/AppInput.vue";
import AppSearchInput from "@/shared/components/AppSearchInput.vue";
import AppSelect from "@/shared/components/AppSelect.vue";
import AppTextarea from "@/shared/components/AppTextarea.vue";
import AppButton from "@/shared/components/AppButton.vue";
import AppModal from "@/shared/components/AppModal.vue";
import { useDateFormat } from "@/shared/composables/useDateFormat.js";
import { useFileSize } from "@/shared/composables/useFileSize.js";
import { statusBadge, statusBadgeColor } from "@/shared/utils/statusStyles.js";
import AppBadge from "@/shared/components/AppBadge.vue";
import AppPagination from "@/shared/components/AppPagination.vue";
import { useAdminUsers } from "@core/admin/administration/composables/useAdminUsers.js";
import { useAdminParameters } from "@core/admin/administration/composables/useAdminParameters.js";
import { useAdminAccessRequests } from "@core/admin/administration/composables/useAdminAccessRequests.js";
import DashboardOverview from "@core/admin/dashboard/DashboardOverview.vue";
import AdminUserBadges from "@core/admin/administration/AdminUserBadges.vue";
import AdminUserActions from "@core/admin/administration/AdminUserActions.vue";
import AdminAccessRequestStatusBadge from "@core/admin/administration/AdminAccessRequestStatusBadge.vue";
import AdminAccessRequestActions from "@core/admin/administration/AdminAccessRequestActions.vue";
import {
    LayoutDashboard,
    Sliders,
    FileText,
    Image as ImageIcon,
    Menu as MenuIcon,
    Users,
    Plus,
    Trash2,
    KeyRound,
    ScrollText,
    ShieldCheck,
    Save, } from "lucide-vue-next";

const LOCALE_OPTIONS = [
    { value: "fr", label: "Français" },
    { value: "en", label: "English" },
    { value: "es", label: "Español" },
    { value: "de", label: "Deutsch" },
];

const { t } = useI18n();
const { formatDateShort, formatDateTime } = useDateFormat();
const { formatSize } = useFileSize();

const tabNav = ref(null);

onMounted(() => {
    const active = tabNav.value?.querySelector('[aria-current="page"]');
    active?.scrollIntoView({ block: 'nearest', inline: 'center' });
});

const props = defineProps({
    tab: { type: String, default: "overview" },
    stats: { type: Object, default: () => ({}) },
    parameters: { type: Object, default: () => ({}) },
    users: { type: Object, default: () => ({}) },
    accessRequests: { type: Object, default: () => ({}) },
    audit: { type: Object, default: () => ({}) },
    permissions: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    overviewPath: { type: String, required: true },
    parametersPath: { type: String, required: true },
    parameterUpdatePath: { type: String, required: true },
    usersPath: { type: String, required: true },
    userCreatePath: { type: String, required: true },
    userUpdatePath: { type: String, required: true },
    userToggleRolePath: { type: String, required: true },
    userDeletePath: { type: String, required: true },
    impersonatePath: { type: String, required: true },
    accessRequestsPath: { type: String, required: true },
    auditPath: { type: String, required: true },
    permissionsPath: { type: String, required: true },
    accessRequestApprovePath: { type: String, required: true },
    accessRequestRejectPath: { type: String, required: true },
    accessRequestPurgePath: { type: String, required: true },
    csrfToken: { type: String, default: "" },
});

const parsedStats = computed(() => props.stats ?? {});

const tabs = [
    { key: "overview", label: () => t("admin.tabs.overview"), path: props.overviewPath, icon: LayoutDashboard },
    { key: "parameters", label: () => t("admin.tabs.parameters"), path: props.parametersPath, icon: Sliders },
    { key: "access_requests", label: () => t("admin.tabs.access_requests"), path: props.accessRequestsPath, icon: KeyRound },
    { key: "audit", label: () => t("admin.tabs.audit"), path: props.auditPath, icon: ScrollText },
    { key: "permissions", label: () => t("admin.tabs.permissions"), path: props.permissionsPath, icon: ShieldCheck },
];

const users = useAdminUsers(props.usersPath, props.userCreatePath, props.userUpdatePath, props.userToggleRolePath, props.userDeletePath, props.impersonatePath, props.csrfToken, props.users, props.search);
const parameters = useAdminParameters(props.parametersPath, props.parameterUpdatePath, props.parameters, props.search);
const accessRequests = useAdminAccessRequests(props.accessRequestsPath, props.accessRequestApprovePath, props.accessRequestRejectPath, props.accessRequestPurgePath, props.csrfToken, props.accessRequests);

function onAuditModuleChange(value) {
    const url = new URL(props.auditPath, window.location.origin);
    if (value) {
        url.searchParams.set("module", value);
    }
    window.location.assign(url.toString());
}
</script>

<template>
    <div class="space-y-6">
        <nav ref="tabNav" class="flex gap-1 border-b border-line overflow-x-auto scrollbar-thin">
            <a
                v-for="tabItem in tabs"
                :key="tabItem.key"
                :href="tabItem.path"
                :aria-current="props.tab === tabItem.key ? 'page' : undefined"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                :class="props.tab === tabItem.key
                    ? 'border-accent-500 text-accent-400'
                    : 'border-transparent text-secondary hover:text-primary hover:border-line'"
            >
                <component :is="tabItem.icon" class="w-4 h-4" :stroke-width="2" />
                {{ tabItem.label() }}
            </a>
        </nav>

        <DashboardOverview v-if="props.tab === 'overview'" :stats="parsedStats" />

        <div v-if="props.tab === 'parameters'" class="space-y-3">
            <div>
                <AppSearchInput
                    v-model="parameters.searchInput.value"
                    :placeholder="t('admin.parameters.searchPlaceholder')"
                    v-on:search="parameters.performSearch"
                />
            </div>

            <div class="sm:hidden space-y-3">
                <p v-if="!parameters.items.value?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.parameters.empty') }}</p>
                <div v-for="parameter in parameters.items.value" :key="parameter.key" class="bg-surface border border-line rounded-lg p-4 space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-sm text-accent-400 font-medium break-all">{{ parameter.key }}</p>
                            <p v-if="parameter.label && parameter.label !== parameter.key" class="text-xs text-secondary mt-0.5">{{ parameter.label }}</p>
                        </div>
                        <AppBadge v-if="parameter.group" color="gray" class="shrink-0">{{ parameter.group }}</AppBadge>
                    </div>
                    <div v-if="parameters.editingKey.value === parameter.key" class="space-y-2">
                        <AppInput v-model="parameters.editingValue.value" v-on:keyup.enter="parameters.saveEdit(parameter)" v-on:keyup.esc="parameters.cancelEdit" />
                        <div class="flex gap-2">
                            <AppButton
                                variant="primary"
                                size="md"
                                class="flex-1"
                                :loading="parameters.editSaving.value"
                                v-on:click="parameters.saveEdit(parameter)"
                            >
                                <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}
                            </AppButton>
                            <AppButton variant="ghost" size="md" class="flex-1" v-on:click="parameters.cancelEdit">{{ t('shared.common.cancel') }}</AppButton>
                        </div>
                    </div>
                    <button v-else type="button" class="text-left w-full px-2 py-1 rounded-md text-primary hover:bg-surface-2 transition-colors text-sm font-medium break-all" v-on:click="parameters.startEdit(parameter)">
                        <span v-if="parameter.value !== null && parameter.value !== ''">{{ parameter.value }}</span>
                        <span v-else class="text-muted italic">-</span>
                    </button>
                    <p v-if="parameter.description" class="text-xs text-secondary">{{ parameter.description }}</p>
                </div>
            </div>

            <div class="hidden sm:block bg-surface border border-line rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-line">
                        <tr>
                            <th class="px-5 py-3 text-left text-sm font-semibold text-primary w-1/3">{{ t('admin.parameters.key') }}</th>
                            <th class="px-5 py-3 text-left text-sm font-semibold text-primary w-1/4">{{ t('admin.parameters.value') }}</th>
                            <th class="px-5 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ t('admin.parameters.description') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="parameter in parameters.items.value" :key="parameter.key" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-5 py-3 align-top w-1/3">
                                <p class="font-mono text-sm text-accent-400 font-medium break-all">{{ parameter.key }}</p>
                                <p v-if="parameter.label && parameter.label !== parameter.key" class="text-xs text-secondary mt-0.5">{{ parameter.label }}</p>
                                <AppBadge v-if="parameter.group" color="gray" class="mt-1">{{ parameter.group }}</AppBadge>
                            </td>
                            <td class="px-5 py-3 align-top w-1/4">
                                <div v-if="parameters.editingKey.value === parameter.key" class="space-y-2">
                                    <AppInput v-model="parameters.editingValue.value" v-on:keyup.enter="parameters.saveEdit(parameter)" v-on:keyup.esc="parameters.cancelEdit" />
                                    <div class="flex gap-2">
                                        <AppButton variant="primary" size="md" :loading="parameters.editSaving.value" v-on:click="parameters.saveEdit(parameter)"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                                        <AppButton variant="ghost" size="md" v-on:click="parameters.cancelEdit">{{ t('shared.common.cancel') }}</AppButton>
                                    </div>
                                </div>
                                <button v-else type="button" class="text-left w-full px-2 py-1 rounded-md text-primary hover:bg-surface-2 transition-colors font-medium" v-on:click="parameters.startEdit(parameter)">
                                    <span v-if="parameter.value !== null && parameter.value !== ''">{{ parameter.value }}</span>
                                    <span v-else class="text-muted italic">-</span>
                                </button>
                            </td>
                            <td class="px-5 py-3 align-top text-sm text-secondary hidden md:table-cell max-w-md">{{ parameter.description }}</td>
                        </tr>
                        <tr v-if="!parameters.items.value?.length">
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-muted">{{ t('admin.parameters.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AppPagination
                v-if="parameters.totalPages.value > 1"
                :page="parameters.page.value"
                :total-pages="parameters.totalPages.value"
                v-on:change="parameters.goToPage"
            />
        </div>

        <div v-if="props.tab === 'users'" class="space-y-4">
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
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ user.name }}</p>
                            <p class="text-xs text-secondary truncate">{{ user.email }}</p>
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
                                <p class="font-medium text-primary inline-flex items-center gap-1.5">
                                    {{ user.name }}
                                    <AppBadge v-if="user.isCurrent" color="accent">{{ t('admin.users.you') }}</AppBadge>
                                </p>
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


        <div v-if="props.tab === 'access_requests'" class="space-y-4">
            <div class="flex justify-end">
                <AppButton variant="danger" size="md" v-on:click="accessRequests.confirmPurge.value = true">
                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t('admin.access_requests.purge') }}
                </AppButton>
            </div>

            <div class="sm:hidden space-y-3">
                <p v-if="!accessRequests.items.value?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.access_requests.empty') }}</p>
                <div v-for="accessRequest in accessRequests.items.value" :key="accessRequest.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ accessRequest.requesterName ?? '-' }}</p>
                            <p class="text-xs text-secondary truncate">{{ accessRequest.requesterEmail }}</p>
                        </div>
                        <AdminAccessRequestStatusBadge
                            :access-request="accessRequest"
                            :status-label="accessRequests.statusLabel.value"
                            class="shrink-0"
                        />
                    </div>
                    <p v-if="accessRequest.message" class="text-sm text-secondary">{{ accessRequest.message }}</p>
                    <div class="flex items-center justify-between pt-1 border-t border-line">
                        <p class="text-xs text-muted">{{ formatDateShort(accessRequest.createdAt) }} · expire {{ formatDateShort(accessRequest.expiresAt) }}</p>
                        <div class="flex items-center gap-1">
                            <AdminAccessRequestActions
                                :access-request="accessRequest"
                                v-on:approve="accessRequests.openApproveModal"
                                v-on:reject="(ar) => (accessRequests.pendingReject.value = ar)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-line">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary">{{ t('admin.access_requests.requester') }}</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ t('admin.access_requests.message') }}</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary">{{ t('admin.access_requests.status') }}</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t('admin.access_requests.date') }}</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t('admin.access_requests.expires') }}</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-primary">{{ t('admin.users.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="accessRequest in accessRequests.items.value" :key="accessRequest.id" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-medium text-primary">{{ accessRequest.requesterName ?? '-' }}</p>
                                <p class="text-xs text-secondary">{{ accessRequest.requesterEmail }}</p>
                            </td>
                            <td class="px-6 py-3 max-w-xs hidden md:table-cell">
                                <p class="text-sm text-secondary truncate">{{ accessRequest.message ?? '-' }}</p>
                            </td>
                            <td class="px-6 py-3">
                                <AdminAccessRequestStatusBadge
                                    :access-request="accessRequest"
                                    :status-label="accessRequests.statusLabel.value"
                                />
                            </td>
                            <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(accessRequest.createdAt) }}</td>
                            <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(accessRequest.expiresAt) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <AdminAccessRequestActions
                                        :access-request="accessRequest"
                                        v-on:approve="accessRequests.openApproveModal"
                                        v-on:reject="(ar) => (accessRequests.pendingReject.value = ar)"
                                    />
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!accessRequests.items.value?.length">
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.access_requests.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <AppPagination
                v-if="accessRequests.totalPages.value > 1"
                :page="accessRequests.page.value"
                :total-pages="accessRequests.totalPages.value"
                v-on:change="accessRequests.goToPage"
            />

            <AppModal :show="!!accessRequests.pendingApprove.value" max-width="sm" v-on:close="accessRequests.pendingApprove.value = null">
                <p class="text-sm text-primary">{{ t('admin.access_requests.approveConfirm', { name: accessRequests.pendingApprove.value?.requesterName ?? accessRequests.pendingApprove.value?.requesterEmail }) }}</p>
                <div class="flex justify-end gap-2">
                    <AppButton variant="ghost" size="md" v-on:click="accessRequests.pendingApprove.value = null">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" v-on:click="accessRequests.doApproveRequest">{{ t('admin.access_requests.approve') }}</AppButton>
                </div>
            </AppModal>

            <AppModal :show="!!accessRequests.pendingReject.value" max-width="sm" v-on:close="accessRequests.pendingReject.value = null">
                <p class="text-sm text-primary">{{ t('admin.access_requests.rejectConfirm', { name: accessRequests.pendingReject.value?.requesterName ?? accessRequests.pendingReject.value?.requesterEmail }) }}</p>
                <div class="flex justify-end gap-2">
                    <AppButton variant="ghost" size="md" v-on:click="accessRequests.pendingReject.value = null">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="accessRequests.doRejectRequest">{{ t('admin.access_requests.reject') }}</AppButton>
                </div>
            </AppModal>

            <AppModal :show="accessRequests.confirmPurge.value" max-width="sm" v-on:close="accessRequests.confirmPurge.value = false">
                <p class="text-sm text-primary">{{ t('admin.access_requests.purgeConfirm') }}</p>
                <div class="flex justify-end gap-2">
                    <AppButton variant="ghost" size="md" v-on:click="accessRequests.confirmPurge.value = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="accessRequests.doPurge">{{ t('admin.access_requests.purge') }}</AppButton>
                </div>
            </AppModal>
        </div>

        <div v-if="props.tab === 'permissions'" class="space-y-6">
            <p class="text-sm text-secondary">{{ t('admin.permissions.intro') }}</p>
            <p v-if="!props.permissions?.modules?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.permissions.empty') }}</p>
            <div v-for="moduleEntry in props.permissions?.modules ?? []" :key="moduleEntry.id" class="bg-surface border border-line rounded-lg overflow-hidden">
                <div class="bg-surface-2 border-b border-line px-4 py-2.5">
                    <h3 class="text-sm font-semibold text-primary capitalize">{{ moduleEntry.id }}</h3>
                </div>
                <p v-if="!moduleEntry.permissions.length" class="px-4 py-3 text-xs text-muted">{{ t('admin.permissions.none') }}</p>
                <table v-else class="w-full text-sm">
                    <thead class="border-b border-line">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-secondary text-xs uppercase tracking-wide">{{ t('admin.permissions.name') }}</th>
                            <th class="px-4 py-2 text-left font-semibold text-secondary text-xs uppercase tracking-wide">{{ t('admin.permissions.role') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="permission in moduleEntry.permissions" :key="permission.name">
                            <td class="px-4 py-2"><span class="font-mono text-xs text-accent-400">{{ permission.name }}</span></td>
                            <td class="px-4 py-2"><span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-surface-2 text-secondary">{{ permission.role }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="props.tab === 'audit'" class="space-y-3">
            <div v-if="props.audit?.modules?.length" class="max-w-xs">
                <AppSelect
                    :model-value="props.audit?.module ?? ''"
                    :label="t('admin.audit.module')"
                    v-on:update:model-value="onAuditModuleChange"
                >
                    <option value="">{{ t('shared.common.all') }}</option>
                    <option v-for="moduleName in props.audit.modules" :key="moduleName" :value="moduleName">{{ moduleName }}</option>
                </AppSelect>
            </div>
            <p v-if="!props.audit?.items?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.audit.empty') }}</p>
            <div v-else class="bg-surface border border-line rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-line">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-primary">{{ t('admin.audit.action') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-primary hidden sm:table-cell">{{ t('admin.audit.module') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-primary hidden md:table-cell">{{ t('admin.audit.user') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-primary">{{ t('admin.audit.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="log in props.audit.items" :key="log.id" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs text-accent-400">{{ log.action }}</span>
                                <span v-if="log.entityType" class="ml-2 text-muted text-xs">{{ log.entityType }} #{{ log.entityId }}</span>
                                <span v-if="log.data?.name" class="ml-2 text-secondary text-xs truncate">— {{ log.data.name }}</span>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-surface-2 text-secondary">{{ log.module }}</span>
                            </td>
                            <td class="px-4 py-3 text-secondary text-xs hidden md:table-cell">
                                <template v-if="log.userName">{{ log.userName }}</template>
                                <template v-if="log.userName && log.userEmail"> · </template>
                                <span v-if="log.userEmail" class="text-muted">{{ log.userEmail }}</span>
                                <template v-if="!log.userName && !log.userEmail">—</template>
                            </td>
                            <td class="px-4 py-3 text-secondary text-xs">{{ formatDateTime(log.createdAt) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
