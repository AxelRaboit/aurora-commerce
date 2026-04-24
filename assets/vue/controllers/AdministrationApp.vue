<script setup>
import { computed, ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import AppInput from "@/components/AppInput.vue";
import AppSelect from "@/components/AppSelect.vue";
import AppTextarea from "@/components/AppTextarea.vue";
import AppButton from "@/components/AppButton.vue";
import AppModal from "@/components/AppModal.vue";
import { useDateFormat } from "@/composables/useDateFormat.js";
import { useFileSize } from "@/composables/useFileSize.js";
import { statusBadge } from "@/utils/statusStyles.js";
import { useAdminUsers } from "@/admin/administration/composables/useAdminUsers.js";
import { useAdminParameters } from "@/admin/administration/composables/useAdminParameters.js";
import { useAdminInvitations } from "@/admin/administration/composables/useAdminInvitations.js";
import { useAdminAccessRequests } from "@/admin/administration/composables/useAdminAccessRequests.js";
import {
    LayoutDashboard,
    Sliders,
    FileText,
    Image as ImageIcon,
    Menu as MenuIcon,
    Users,
    Plus,
    Pencil,
    Trash2,
    LogIn,
    Mail,
    KeyRound,
    Check,
    X,
    Clock,
    Shield,
    ShieldCheck,
    UserRound,
} from "lucide-vue-next";

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
    invitationsPath: { type: String, required: true },
    invitationSendPath: { type: String, required: true },
    accessRequestsPath: { type: String, required: true },
    accessRequestApprovePath: { type: String, required: true },
    accessRequestRejectPath: { type: String, required: true },
    accessRequestPurgePath: { type: String, required: true },
    csrfToken: { type: String, default: "" },
});

const parsedStats = computed(() => props.stats ?? {});

const tabs = [
    { key: "overview", label: () => t("admin.tabs.overview"), path: props.overviewPath, icon: LayoutDashboard },
    { key: "users", label: () => t("admin.tabs.users"), path: props.usersPath, icon: Users },
    { key: "invitations", label: () => t("admin.tabs.invitations"), path: props.invitationsPath, icon: Mail },
    { key: "parameters", label: () => t("admin.tabs.parameters"), path: props.parametersPath, icon: Sliders },
    { key: "access_requests", label: () => t("admin.tabs.access_requests"), path: props.accessRequestsPath, icon: KeyRound },
];

const users = useAdminUsers(props.usersPath, props.userCreatePath, props.userUpdatePath, props.userToggleRolePath, props.userDeletePath, props.impersonatePath, props.csrfToken, props.users, props.search);
const parameters = useAdminParameters(props.parameterUpdatePath, props.parameters);
const invitations = useAdminInvitations(props.invitationSendPath, props.csrfToken);
const accessRequests = useAdminAccessRequests(props.accessRequestsPath, props.accessRequestApprovePath, props.accessRequestRejectPath, props.accessRequestPurgePath, props.csrfToken, props.accessRequests);
</script>

<template>
    <div class="space-y-6">
        <nav ref="tabNav" class="flex gap-1 border-b border-line overflow-x-auto">
            <a
                v-for="tabItem in tabs"
                :key="tabItem.key"
                :href="tabItem.path"
                :aria-current="props.tab === tabItem.key ? 'page' : undefined"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                :class="props.tab === tabItem.key
                    ? 'border-indigo-500 text-indigo-400'
                    : 'border-transparent text-secondary hover:text-primary hover:border-line'"
            >
                <component :is="tabItem.icon" class="w-4 h-4" :stroke-width="2" />
                {{ tabItem.label() }}
            </a>
        </nav>

        <div v-if="props.tab === 'overview'" class="space-y-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.posts') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <FileText class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-indigo-400">{{ parsedStats.posts?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">
                        {{ parsedStats.posts?.published ?? 0 }} {{ t('admin.stats.published') }} ·
                        {{ parsedStats.posts?.draft ?? 0 }} {{ t('admin.stats.draft') }}
                    </p>
                </div>

                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.media') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                            <ImageIcon class="w-4 h-4 text-sky-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-sky-400">{{ parsedStats.media?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ formatSize(parsedStats.media?.totalSize ?? 0) }}</p>
                </div>

                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.menus') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <MenuIcon class="w-4 h-4 text-amber-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-amber-400">{{ parsedStats.menus?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">&nbsp;</p>
                </div>

                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ t('admin.stats.users') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <Users class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-400">{{ parsedStats.users?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">&nbsp;</p>
                </div>
            </div>

            <div v-if="parsedStats.posts?.byType?.length" class="bg-surface border border-line/60 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-primary mb-4">{{ t('admin.stats.byType') }}</h3>
                <div class="space-y-2">
                    <div v-for="item in parsedStats.posts.byType" :key="item.slug" class="flex items-center justify-between text-sm">
                        <span class="text-secondary">{{ item.label }}</span>
                        <span class="font-medium text-primary tabular-nums">{{ item.count }}</span>
                    </div>
                </div>
            </div>

            <div v-if="parsedStats.recentPosts?.length" class="bg-surface border border-line/60 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-primary mb-4">{{ t('admin.stats.recent') }}</h3>
                <div class="space-y-3">
                    <div v-for="post in parsedStats.recentPosts" :key="post.id" class="flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-primary truncate">{{ post.title }}</div>
                            <div class="text-xs text-muted">{{ post.postType }} · {{ formatDateTime(post.updatedAt) }}</div>
                        </div>
                        <span class="px-2 py-0.5 text-xs rounded-md" :class="statusBadge(post.status)">{{ t(`admin.stats.postStatus.${post.status}`, post.status) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="props.tab === 'parameters'" class="space-y-3">
            <div class="sm:hidden space-y-3">
                <p v-if="!parameters.parsedParameters.value.items?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.parameters.empty') }}</p>
                <div v-for="parameter in parameters.parsedParameters.value.items" :key="parameter.key" class="bg-surface border border-line rounded-lg p-4 space-y-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-sm text-indigo-400 font-medium break-all">{{ parameter.key }}</p>
                            <p v-if="parameter.label && parameter.label !== parameter.key" class="text-xs text-secondary mt-0.5">{{ parameter.label }}</p>
                        </div>
                        <span v-if="parameter.group" class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full bg-surface-2 text-muted shrink-0">{{ parameter.group }}</span>
                    </div>
                    <div v-if="parameters.editingKey.value === parameter.key" class="space-y-2">
                        <input v-model="parameters.editingValue.value" class="w-full bg-surface-2 text-primary rounded-md px-2 py-1.5 border border-line focus:border-indigo-500 focus:outline-none text-sm" v-on:keyup.enter="parameters.saveEdit(parameter)" v-on:keyup.esc="parameters.cancelEdit">
                        <div class="flex gap-2">
                            <AppButton
                                variant="primary"
                                size="md"
                                class="flex-1"
                                :loading="parameters.editSaving.value"
                                v-on:click="parameters.saveEdit(parameter)"
                            >
                                {{ t('common.save') }}
                            </AppButton>
                            <AppButton variant="ghost" size="md" class="flex-1" v-on:click="parameters.cancelEdit">{{ t('common.cancel') }}</AppButton>
                        </div>
                    </div>
                    <button v-else type="button" class="text-left w-full px-2 py-1 rounded-md text-primary hover:bg-surface-2 transition-colors text-sm font-medium" v-on:click="parameters.startEdit(parameter)">
                        <span v-if="parameter.value !== null && parameter.value !== ''">{{ parameter.value }}</span>
                        <span v-else class="text-muted italic">—</span>
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
                        <tr v-for="parameter in parameters.parsedParameters.value.items" :key="parameter.key" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-5 py-3 align-top w-1/3">
                                <p class="font-mono text-sm text-indigo-400 font-medium break-all">{{ parameter.key }}</p>
                                <p v-if="parameter.label && parameter.label !== parameter.key" class="text-xs text-secondary mt-0.5">{{ parameter.label }}</p>
                                <span v-if="parameter.group" class="mt-1 inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full bg-surface-2 text-muted">{{ parameter.group }}</span>
                            </td>
                            <td class="px-5 py-3 align-top w-1/4">
                                <div v-if="parameters.editingKey.value === parameter.key" class="flex items-center gap-2">
                                    <input v-model="parameters.editingValue.value" class="flex-1 bg-surface-2 text-primary rounded-md px-2 py-1 border border-line focus:border-indigo-500 focus:outline-none text-sm" v-on:keyup.enter="parameters.saveEdit(parameter)" v-on:keyup.esc="parameters.cancelEdit">
                                    <AppButton variant="primary" size="md" :loading="parameters.editSaving.value" v-on:click="parameters.saveEdit(parameter)">{{ t('common.save') }}</AppButton>
                                    <AppButton variant="ghost" size="md" v-on:click="parameters.cancelEdit">{{ t('common.cancel') }}</AppButton>
                                </div>
                                <button v-else type="button" class="text-left w-full px-2 py-1 rounded-md text-primary hover:bg-surface-2 transition-colors font-medium" v-on:click="parameters.startEdit(parameter)">
                                    <span v-if="parameter.value !== null && parameter.value !== ''">{{ parameter.value }}</span>
                                    <span v-else class="text-muted italic">—</span>
                                </button>
                            </td>
                            <td class="px-5 py-3 align-top text-sm text-secondary hidden md:table-cell max-w-md">{{ parameter.description }}</td>
                        </tr>
                        <tr v-if="!parameters.parsedParameters.value.items?.length">
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-muted">{{ t('admin.parameters.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="props.tab === 'users'" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2">
                <input
                    v-model="users.searchInput.value"
                    type="text"
                    :placeholder="t('admin.users.searchPlaceholder')"
                    class="w-full px-4 py-2 rounded-lg bg-surface-2 border border-line text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    v-on:keyup.enter="users.performSearch"
                >
                <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="users.performSearch">{{ t('admin.users.search') }}</AppButton>
                <AppButton variant="secondary" size="md" class="w-full sm:w-auto" v-on:click="users.openCreate">
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
                            <span v-if="user.isCurrent" class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-500/15 text-indigo-400">{{ t('admin.users.you') }}</span>
                            <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full" :class="user.isDevRole ? 'bg-indigo-500/15 text-indigo-400' : 'bg-surface-2 text-muted'">
                                {{ user.isDevRole ? t('admin.users.role_dev') : t('admin.users.role_user') }}
                            </span>
                            <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full bg-surface-2 text-muted uppercase">{{ user.locale }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-line">
                        <p class="text-xs text-muted">{{ formatDateShort(user.createdAt) }}</p>
                        <div class="flex items-center gap-1">
                            <button type="button" class="p-1.5 text-muted hover:text-indigo-400 transition-colors rounded" :title="t('admin.users.edit')" v-on:click="users.openEdit(user)">
                                <Pencil class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <a v-if="!user.isCurrent" :href="users.impersonatePath.replace('__email__', encodeURIComponent(user.email))" class="p-1.5 text-muted hover:text-amber-400 transition-colors rounded" :title="t('admin.users.impersonate', { name: user.name })">
                                <LogIn class="w-4 h-4" :stroke-width="2" />
                            </a>
                            <button
                                v-if="!user.isCurrent"
                                type="button"
                                class="p-1.5 text-muted transition-colors rounded"
                                :class="user.isDevRole ? 'hover:text-indigo-400' : 'hover:text-rose-400'"
                                :title="user.isDevRole ? t('admin.users.revoke_dev') : t('admin.users.grant_dev')"
                                v-on:click="users.confirmToggleRole(user)"
                            >
                                <component :is="user.isDevRole ? UserRound : Shield" class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <button
                                v-if="!user.isCurrent"
                                type="button"
                                class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded"
                                :title="t('common.delete')"
                                v-on:click="users.confirmDelete(user)"
                            >
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto">
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
                                    <span v-if="user.isCurrent" class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-500/15 text-indigo-400">{{ t('admin.users.you') }}</span>
                                </p>
                            </td>
                            <td class="px-6 py-3 text-secondary">{{ user.email }}</td>
                            <td class="px-6 py-3 hidden md:table-cell">
                                <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full" :class="user.isDevRole ? 'bg-indigo-500/15 text-indigo-400' : 'bg-surface-2 text-muted'">
                                    {{ user.isDevRole ? t('admin.users.role_dev') : t('admin.users.role_user') }}
                                </span>
                            </td>
                            <td class="px-6 py-3 hidden lg:table-cell">
                                <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full bg-surface-2 text-muted uppercase">{{ user.locale }}</span>
                            </td>
                            <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(user.createdAt) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" class="p-1.5 text-muted hover:text-indigo-400 transition-colors rounded" :title="t('admin.users.edit')" v-on:click="users.openEdit(user)">
                                        <Pencil class="w-4 h-4" :stroke-width="2" />
                                    </button>
                                    <a v-if="!user.isCurrent" :href="users.impersonatePath.replace('__email__', encodeURIComponent(user.email))" class="p-1.5 text-muted hover:text-amber-400 transition-colors rounded" :title="t('admin.users.impersonate', { name: user.name })">
                                        <LogIn class="w-4 h-4" :stroke-width="2" />
                                    </a>
                                    <button
                                        v-if="!user.isCurrent"
                                        type="button"
                                        class="p-1.5 text-muted transition-colors rounded"
                                        :class="user.isDevRole ? 'hover:text-indigo-400' : 'hover:text-rose-400'"
                                        :title="user.isDevRole ? t('admin.users.revoke_dev') : t('admin.users.grant_dev')"
                                        v-on:click="users.confirmToggleRole(user)"
                                    >
                                        <component :is="user.isDevRole ? UserRound : Shield" class="w-4 h-4" :stroke-width="2" />
                                    </button>
                                    <button
                                        v-if="!user.isCurrent"
                                        type="button"
                                        class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded"
                                        :title="t('common.delete')"
                                        v-on:click="users.confirmDelete(user)"
                                    >
                                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                                    </button>
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
                    <AppButton variant="ghost" size="md" v-on:click="users.pendingDelete.value = null">{{ t('common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="users.doDelete">{{ t('common.delete') }}</AppButton>
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
                        <AppButton variant="ghost" size="md" v-on:click="users.showCreateModal.value = false">{{ t('common.cancel') }}</AppButton>
                        <AppButton type="submit" variant="primary" size="md" :loading="users.createLoading.value">{{ t('common.create') }}</AppButton>
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
                        <AppButton variant="ghost" size="md" v-on:click="users.closeEdit">{{ t('common.cancel') }}</AppButton>
                        <AppButton type="submit" variant="primary" size="md" :loading="users.editLoading.value">{{ t('common.save') }}</AppButton>
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
                    <AppButton variant="ghost" size="md" v-on:click="users.pendingToggleRole.value = null">{{ t('common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" v-on:click="users.doToggleRole">{{ t('common.confirm') }}</AppButton>
                </div>
            </AppModal>
        </div>

        <div v-if="props.tab === 'invitations'" class="max-w-lg space-y-4">
            <p class="text-sm text-secondary">{{ t('admin.invitations.description') }}</p>
            <form class="space-y-4" v-on:submit.prevent="invitations.submitInvitation">
                <AppInput
                    v-model="invitations.invitationEmail.value"
                    type="email"
                    :label="t('admin.invitations.email')"
                    :placeholder="t('admin.invitations.emailPlaceholder')"
                    :error="invitations.invitationErrors.value.email"
                    required
                />
                <AppTextarea v-model="invitations.invitationMessage.value" :label="t('admin.invitations.message')" :placeholder="t('admin.invitations.messagePlaceholder')" :rows="5" />
                <div class="border border-line rounded-lg p-4 space-y-3 bg-surface-2/50">
                    <p class="text-xs text-secondary">{{ t('admin.invitations.credentialsHint') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <AppInput v-model="invitations.invitationCredentialEmail.value" type="email" :label="t('admin.invitations.credentialEmail')" :placeholder="t('admin.invitations.emailPlaceholder')" />
                        <AppInput v-model="invitations.invitationCredentialPassword.value" :label="t('admin.invitations.credentialPassword')" />
                    </div>
                </div>
                <AppButton
                    type="submit"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    :loading="invitations.invitationSending.value"
                >
                    <Mail v-if="!invitations.invitationSending.value" class="w-4 h-4" :stroke-width="2" />
                    {{ t('admin.invitations.send') }}
                </AppButton>
            </form>
        </div>

        <div v-if="props.tab === 'access_requests'" class="space-y-4">
            <div class="flex justify-end">
                <AppButton variant="danger-outline" size="md" v-on:click="accessRequests.confirmPurge.value = true">
                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t('admin.access_requests.purge') }}
                </AppButton>
            </div>

            <div class="sm:hidden space-y-3">
                <p v-if="!accessRequests.parsedAccessRequests.value.items?.length" class="py-8 text-center text-sm text-muted">{{ t('admin.access_requests.empty') }}</p>
                <div v-for="accessRequest in accessRequests.parsedAccessRequests.value.items" :key="accessRequest.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ accessRequest.requesterName ?? '—' }}</p>
                            <p class="text-xs text-secondary truncate">{{ accessRequest.requesterEmail }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full shrink-0" :class="accessRequests.statusBadge(accessRequest.status)">
                            <component :is="accessRequest.status === 'pending' ? Clock : accessRequest.status === 'approved' ? ShieldCheck : X" class="w-3 h-3" :stroke-width="2.5" />
                            {{ accessRequests.statusLabel.value[accessRequest.status] ?? accessRequest.status }}
                        </span>
                    </div>
                    <p v-if="accessRequest.message" class="text-sm text-secondary">{{ accessRequest.message }}</p>
                    <div class="flex items-center justify-between pt-1 border-t border-line">
                        <p class="text-xs text-muted">{{ formatDateShort(accessRequest.createdAt) }} · expire {{ formatDateShort(accessRequest.expiresAt) }}</p>
                        <div v-if="accessRequest.status === 'pending'" class="flex items-center gap-1">
                            <button type="button" class="p-1.5 text-muted hover:text-emerald-400 transition-colors rounded" :title="t('admin.access_requests.approve')" v-on:click="accessRequests.openApproveModal(accessRequest)">
                                <Check class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <button type="button" class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" :title="t('admin.access_requests.reject')" v-on:click="accessRequests.pendingReject.value = accessRequest">
                                <X class="w-4 h-4" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto">
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
                        <tr v-for="accessRequest in accessRequests.parsedAccessRequests.value.items" :key="accessRequest.id" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-6 py-3">
                                <p class="font-medium text-primary">{{ accessRequest.requesterName ?? '—' }}</p>
                                <p class="text-xs text-secondary">{{ accessRequest.requesterEmail }}</p>
                            </td>
                            <td class="px-6 py-3 max-w-xs hidden md:table-cell">
                                <p class="text-sm text-secondary truncate">{{ accessRequest.message ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full" :class="accessRequests.statusBadge(accessRequest.status)">
                                    <component :is="accessRequest.status === 'pending' ? Clock : accessRequest.status === 'approved' ? ShieldCheck : X" class="w-3 h-3" :stroke-width="2.5" />
                                    {{ accessRequests.statusLabel.value[accessRequest.status] ?? accessRequest.status }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(accessRequest.createdAt) }}</td>
                            <td class="px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(accessRequest.expiresAt) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <template v-if="accessRequest.status === 'pending'">
                                        <button type="button" class="p-1.5 text-muted hover:text-emerald-400 transition-colors rounded" :title="t('admin.access_requests.approve')" v-on:click="accessRequests.openApproveModal(accessRequest)">
                                            <Check class="w-4 h-4" :stroke-width="2" />
                                        </button>
                                        <button type="button" class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" :title="t('admin.access_requests.reject')" v-on:click="accessRequests.pendingReject.value = accessRequest">
                                            <X class="w-4 h-4" :stroke-width="2" />
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!accessRequests.parsedAccessRequests.value.items?.length">
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.access_requests.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <AppModal :show="!!accessRequests.pendingApprove.value" max-width="sm" v-on:close="accessRequests.pendingApprove.value = null">
                <p class="text-sm text-primary">{{ t('admin.access_requests.approveConfirm', { name: accessRequests.pendingApprove.value?.requesterName ?? accessRequests.pendingApprove.value?.requesterEmail }) }}</p>
                <div class="flex justify-end gap-2">
                    <AppButton variant="ghost" size="md" v-on:click="accessRequests.pendingApprove.value = null">{{ t('common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" v-on:click="accessRequests.doApproveRequest">{{ t('admin.access_requests.approve') }}</AppButton>
                </div>
            </AppModal>

            <AppModal :show="!!accessRequests.pendingReject.value" max-width="sm" v-on:close="accessRequests.pendingReject.value = null">
                <p class="text-sm text-primary">{{ t('admin.access_requests.rejectConfirm', { name: accessRequests.pendingReject.value?.requesterName ?? accessRequests.pendingReject.value?.requesterEmail }) }}</p>
                <div class="flex justify-end gap-2">
                    <AppButton variant="ghost" size="md" v-on:click="accessRequests.pendingReject.value = null">{{ t('common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="accessRequests.doRejectRequest">{{ t('admin.access_requests.reject') }}</AppButton>
                </div>
            </AppModal>

            <AppModal :show="accessRequests.confirmPurge.value" max-width="sm" v-on:close="accessRequests.confirmPurge.value = false">
                <p class="text-sm text-primary">{{ t('admin.access_requests.purgeConfirm') }}</p>
                <div class="flex justify-end gap-2">
                    <AppButton variant="ghost" size="md" v-on:click="accessRequests.confirmPurge.value = false">{{ t('common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="accessRequests.doPurge">{{ t('admin.access_requests.purge') }}</AppButton>
                </div>
            </AppModal>
        </div>
    </div>
</template>
