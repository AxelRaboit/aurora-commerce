<script setup>
import { computed, ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import AppInput from "@/components/AppInput.vue";
import AppTextarea from "@/components/AppTextarea.vue";
import { useDateFormat } from "@/composables/useDateFormat.js";
import { useFileSize } from "@/composables/useFileSize.js";
import { statusBadge } from "@/utils/statusStyles.js";
import { useAdminUsers } from "./composables/useAdminUsers.js";
import { useAdminParameters } from "./composables/useAdminParameters.js";
import { useAdminInvitations } from "./composables/useAdminInvitations.js";
import { useAdminAccessRequests } from "./composables/useAdminAccessRequests.js";
import {
    LayoutDashboard,
    Sliders,
    FileText,
    Image as ImageIcon,
    Menu as MenuIcon,
    Users,
    Plus,
    Trash2,
    LogIn,
    Mail,
    KeyRound,
    Check,
    X,
    Clock,
    ShieldCheck,
} from "lucide-vue-next";

const { t: translate } = useI18n();
const { formatDateShort, formatDateTime } = useDateFormat();
const { formatSize } = useFileSize();

const tabNav = ref(null);

onMounted(() => {
    const active = tabNav.value?.querySelector('[aria-current="page"]');
    active?.scrollIntoView({ block: 'nearest', inline: 'center' });
});

const props = defineProps({
    tab: { type: String, default: "overview" },
    stats: { type: String, default: "{}" },
    parameters: { type: String, default: "{}" },
    users: { type: String, default: "{}" },
    accessRequests: { type: String, default: "{}" },
    search: { type: String, default: "" },
    overviewPath: { type: String, required: true },
    parametersPath: { type: String, required: true },
    parameterUpdatePath: { type: String, required: true },
    usersPath: { type: String, required: true },
    userCreatePath: { type: String, required: true },
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

const parsedStats = computed(() => { try { return JSON.parse(props.stats); } catch { return {}; } });

const tabs = [
    { key: "overview", label: () => translate("admin.tabs.overview"), path: props.overviewPath, icon: LayoutDashboard },
    { key: "users", label: () => translate("admin.tabs.users"), path: props.usersPath, icon: Users },
    { key: "invitations", label: () => translate("admin.tabs.invitations"), path: props.invitationsPath, icon: Mail },
    { key: "parameters", label: () => translate("admin.tabs.parameters"), path: props.parametersPath, icon: Sliders },
    { key: "access_requests", label: () => translate("admin.tabs.access_requests"), path: props.accessRequestsPath, icon: KeyRound },
];

const users = useAdminUsers(props.usersPath, props.userCreatePath, props.userDeletePath, props.impersonatePath, props.csrfToken, props.users, props.search);
const parameters = useAdminParameters(props.parameterUpdatePath, props.parameters);
const invitations = useAdminInvitations(props.invitationSendPath, props.csrfToken);
const accessRequests = useAdminAccessRequests(props.accessRequestsPath, props.accessRequestApprovePath, props.accessRequestRejectPath, props.accessRequestPurgePath, props.csrfToken, props.accessRequests);
</script>

<template>
    <div class="space-y-6">
        <!-- Tabs -->
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

        <!-- Overview -->
        <div v-if="props.tab === 'overview'" class="space-y-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ translate('admin.stats.posts') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 flex items-center justify-center">
                            <FileText class="w-4 h-4 text-indigo-500" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-indigo-400">{{ parsedStats.posts?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">
                        {{ parsedStats.posts?.published ?? 0 }} {{ translate('admin.stats.published') }} ·
                        {{ parsedStats.posts?.draft ?? 0 }} {{ translate('admin.stats.draft') }}
                    </p>
                </div>

                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ translate('admin.stats.media') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                            <ImageIcon class="w-4 h-4 text-sky-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-sky-400">{{ parsedStats.media?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ formatSize(parsedStats.media?.totalSize ?? 0) }}</p>
                </div>

                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ translate('admin.stats.menus') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                            <MenuIcon class="w-4 h-4 text-amber-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-amber-400">{{ parsedStats.menus?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">&nbsp;</p>
                </div>

                <div class="bg-surface border border-line rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-secondary uppercase tracking-wide">{{ translate('admin.stats.users') }}</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                            <Users class="w-4 h-4 text-emerald-400" :stroke-width="2" />
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-400">{{ parsedStats.users?.total ?? 0 }}</p>
                    <p class="text-xs text-muted mt-0.5">&nbsp;</p>
                </div>
            </div>

            <div v-if="parsedStats.posts?.byType?.length" class="bg-surface border border-line/60 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-primary mb-4">{{ translate('admin.stats.byType') }}</h3>
                <div class="space-y-2">
                    <div v-for="item in parsedStats.posts.byType" :key="item.slug" class="flex items-center justify-between text-sm">
                        <span class="text-secondary">{{ item.label }}</span>
                        <span class="font-medium text-primary tabular-nums">{{ item.count }}</span>
                    </div>
                </div>
            </div>

            <div v-if="parsedStats.recentPosts?.length" class="bg-surface border border-line/60 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-primary mb-4">{{ translate('admin.stats.recent') }}</h3>
                <div class="space-y-3">
                    <div v-for="post in parsedStats.recentPosts" :key="post.id" class="flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-primary truncate">{{ post.title }}</div>
                            <div class="text-xs text-muted">{{ post.postType }} · {{ formatDateTime(post.updatedAt) }}</div>
                        </div>
                        <span class="px-2 py-0.5 text-xs rounded-md" :class="statusBadge(post.status)">{{ translate(`admin.stats.postStatus.${post.status}`, post.status) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Parameters -->
        <div v-if="props.tab === 'parameters'" class="space-y-3">
            <!-- Mobile cards -->
            <div class="sm:hidden space-y-3">
                <p v-if="!parameters.parsedParameters.value.items?.length" class="py-8 text-center text-sm text-muted">{{ translate('admin.parameters.empty') }}</p>
                <div v-for="parameter in parameters.parsedParameters.value.items" :key="parameter.key" class="bg-surface border border-line rounded-lg p-4 space-y-2">
                    <div>
                        <p class="font-medium text-primary">{{ parameter.label ?? parameter.key }}</p>
                        <p class="text-xs text-muted font-mono">{{ parameter.key }}</p>
                        <span v-if="parameter.group" class="mt-1 inline-block text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-surface-2 text-muted">{{ parameter.group }}</span>
                    </div>
                    <div v-if="parameters.editingKey.value === parameter.key" class="flex items-center gap-2">
                        <input v-model="parameters.editingValue.value" class="flex-1 bg-surface-2 text-primary rounded-md px-2 py-1 border border-line focus:border-indigo-500 focus:outline-none text-sm" v-on:keyup.enter="parameters.saveEdit(parameter)" v-on:keyup.esc="parameters.cancelEdit">
                        <button type="button" :disabled="parameters.editSaving.value" class="px-2.5 py-1 text-xs font-medium rounded-md bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50" v-on:click="parameters.saveEdit(parameter)">{{ translate('common.save') }}</button>
                        <button type="button" class="px-2.5 py-1 text-xs font-medium rounded-md text-secondary hover:text-primary hover:bg-surface-2" v-on:click="parameters.cancelEdit">{{ translate('common.cancel') }}</button>
                    </div>
                    <button v-else type="button" class="text-left w-full px-2 py-1 rounded-md font-mono text-primary hover:bg-surface-2 transition-colors text-sm" v-on:click="parameters.startEdit(parameter)">
                        <span v-if="parameter.value !== null && parameter.value !== ''">{{ parameter.value }}</span>
                        <span v-else class="text-muted italic">—</span>
                    </button>
                    <p v-if="parameter.description" class="text-xs text-secondary">{{ parameter.description }}</p>
                </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block bg-surface border border-line/60 rounded-xl overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-line">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">{{ translate('admin.parameters.key') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">{{ translate('admin.parameters.value') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted hidden md:table-cell">{{ translate('admin.parameters.description') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="parameter in parameters.parsedParameters.value.items" :key="parameter.key">
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium text-primary">{{ parameter.label ?? parameter.key }}</div>
                                <div class="text-xs text-muted font-mono">{{ parameter.key }}</div>
                                <span v-if="parameter.group" class="mt-1 inline-block text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-surface-2 text-muted">{{ parameter.group }}</span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div v-if="parameters.editingKey.value === parameter.key" class="flex items-center gap-2">
                                    <input v-model="parameters.editingValue.value" class="flex-1 bg-surface-2 text-primary rounded-md px-2 py-1 border border-line focus:border-indigo-500 focus:outline-none text-sm" v-on:keyup.enter="parameters.saveEdit(parameter)" v-on:keyup.esc="parameters.cancelEdit">
                                    <button type="button" :disabled="parameters.editSaving.value" class="px-2.5 py-1 text-xs font-medium rounded-md bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50" v-on:click="parameters.saveEdit(parameter)">{{ translate('common.save') }}</button>
                                    <button type="button" class="px-2.5 py-1 text-xs font-medium rounded-md text-secondary hover:text-primary hover:bg-surface-2" v-on:click="parameters.cancelEdit">{{ translate('common.cancel') }}</button>
                                </div>
                                <button v-else type="button" class="text-left w-full px-2 py-1 rounded-md font-mono text-primary hover:bg-surface-2 transition-colors" v-on:click="parameters.startEdit(parameter)">
                                    <span v-if="parameter.value !== null && parameter.value !== ''">{{ parameter.value }}</span>
                                    <span v-else class="text-muted italic">—</span>
                                </button>
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-secondary hidden md:table-cell max-w-md">{{ parameter.description }}</td>
                        </tr>
                        <tr v-if="!parameters.parsedParameters.value.items?.length">
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-muted">{{ translate('admin.parameters.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users -->
        <div v-if="props.tab === 'users'" class="space-y-4">
            <div class="flex flex-col sm:flex-row gap-2">
                <input v-model="users.searchInput.value" type="text" :placeholder="translate('admin.users.searchPlaceholder')" class="flex-1 px-4 py-2 rounded-lg bg-surface-2 border border-line text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500" v-on:keyup.enter="users.performSearch">
                <button type="button" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-medium" v-on:click="users.performSearch">{{ translate('admin.users.search') }}</button>
                <button type="button" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-surface-2 text-primary hover:bg-surface-3 border border-line" v-on:click="users.openCreate">
                    <Plus class="w-4 h-4" />
                    {{ translate('admin.users.add') }}
                </button>
            </div>

            <!-- Mobile cards -->
            <div class="sm:hidden space-y-3">
                <p v-if="!users.parsedUsers.value.items?.length" class="py-8 text-center text-sm text-muted">{{ translate('admin.users.empty') }}</p>
                <div v-for="user in users.parsedUsers.value.items" :key="user.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <p class="font-medium text-primary truncate">{{ user.name }}</p>
                                <span v-if="user.isCurrent" class="text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500/15 text-indigo-400 shrink-0">{{ translate('admin.users.you') }}</span>
                            </div>
                            <p class="text-xs text-secondary truncate">{{ user.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-line">
                        <p class="text-xs text-muted">{{ formatDateShort(user.createdAt) }}</p>
                        <div class="flex items-center gap-1">
                            <a v-if="!user.isCurrent" :href="users.impersonatePath.replace('__email__', encodeURIComponent(user.email))" class="p-1.5 rounded text-muted hover:text-amber-400 transition-colors" :title="translate('admin.users.impersonate', { name: user.name })">
                                <LogIn class="w-4 h-4" :stroke-width="2" />
                            </a>
                            <button v-if="!user.isCurrent" type="button" class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" v-on:click="users.confirmDelete(user)">
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-line">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">{{ translate('admin.users.name') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">{{ translate('admin.users.email') }}</th>
                            <th class="text-left px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted hidden md:table-cell">{{ translate('admin.users.created') }}</th>
                            <th class="text-right px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">{{ translate('admin.users.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="user in users.parsedUsers.value.items" :key="user.id" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-4 py-3 font-medium text-primary">
                                {{ user.name }}
                                <span v-if="user.isCurrent" class="ml-1 text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-indigo-500/15 text-indigo-400">{{ translate('admin.users.you') }}</span>
                            </td>
                            <td class="px-4 py-3 text-secondary">{{ user.email }}</td>
                            <td class="px-4 py-3 text-secondary hidden md:table-cell">{{ formatDateShort(user.createdAt) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a v-if="!user.isCurrent" :href="users.impersonatePath.replace('__email__', encodeURIComponent(user.email))" class="p-1.5 rounded text-muted hover:text-amber-400 transition-colors" :title="translate('admin.users.impersonate', { name: user.name })">
                                        <LogIn class="w-4 h-4" :stroke-width="2" />
                                    </a>
                                    <button v-if="!user.isCurrent" type="button" class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" v-on:click="users.confirmDelete(user)">
                                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!users.parsedUsers.value.items?.length">
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-muted">{{ translate('admin.users.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Confirm delete modal -->
            <div v-if="users.pendingDelete.value" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-line rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <p class="text-sm text-primary">{{ translate('admin.users.deleteConfirm', { name: users.pendingDelete.value.name }) }}</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="users.pendingDelete.value = null">{{ translate('common.cancel') }}</button>
                        <button type="button" class="px-3 py-1.5 text-sm bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors" v-on:click="users.doDelete">{{ translate('common.delete') }}</button>
                    </div>
                </div>
            </div>

            <!-- Create modal -->
            <div v-if="users.showCreateModal.value" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-line rounded-xl p-6 max-w-md w-full mx-4 space-y-4">
                    <h3 class="text-lg font-semibold text-primary">{{ translate('admin.users.add') }}</h3>
                    <form class="space-y-4" v-on:submit.prevent="users.submitCreate">
                        <AppInput v-model="users.newUser.value.name" :label="translate('admin.users.name')" :error="users.createErrors.value.name" autocomplete="name" required />
                        <AppInput v-model="users.newUser.value.email" type="email" :label="translate('admin.users.email')" :error="users.createErrors.value.email" autocomplete="email" required />
                        <AppInput v-model="users.newUser.value.password" :label="translate('admin.users.password')" :error="users.createErrors.value.password" autocomplete="new-password" toggleable required />
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" class="px-3 py-2 text-sm font-medium rounded-lg text-secondary hover:text-primary hover:bg-surface-2" v-on:click="users.showCreateModal.value = false">{{ translate('common.cancel') }}</button>
                            <button type="submit" :disabled="users.createLoading.value" class="px-3 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50">{{ translate('common.create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Invitations -->
        <div v-if="props.tab === 'invitations'" class="max-w-lg space-y-4">
            <p class="text-sm text-secondary">{{ translate('admin.invitations.description') }}</p>
            <form class="space-y-4" v-on:submit.prevent="invitations.submitInvitation">
                <AppInput v-model="invitations.invitationEmail.value" type="email" :label="translate('admin.invitations.email')" :placeholder="translate('admin.invitations.emailPlaceholder')" :error="invitations.invitationErrors.value.email" required />
                <AppTextarea v-model="invitations.invitationMessage.value" :label="translate('admin.invitations.message')" :placeholder="translate('admin.invitations.messagePlaceholder')" :rows="5" />
                <div class="border border-line rounded-lg p-4 space-y-3 bg-surface-2/50">
                    <p class="text-xs text-secondary">{{ translate('admin.invitations.credentialsHint') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <AppInput v-model="invitations.invitationCredentialEmail.value" type="email" :label="translate('admin.invitations.credentialEmail')" :placeholder="translate('admin.invitations.emailPlaceholder')" />
                        <AppInput v-model="invitations.invitationCredentialPassword.value" :label="translate('admin.invitations.credentialPassword')" />
                    </div>
                </div>
                <button type="submit" :disabled="invitations.invitationSending.value" class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors text-sm font-medium">
                    <Mail class="w-4 h-4" :stroke-width="2" />
                    {{ invitations.invitationSending.value ? translate('admin.invitations.sending') : translate('admin.invitations.send') }}
                </button>
            </form>
        </div>

        <!-- Access Requests -->
        <div v-if="props.tab === 'access_requests'" class="space-y-4">
            <div class="flex justify-end">
                <button class="flex items-center gap-1.5 px-3 py-1.5 text-sm text-muted hover:text-rose-400 hover:bg-rose-500/10 border border-line rounded-lg transition-colors" v-on:click="accessRequests.confirmPurge.value = true">
                    <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ translate('admin.access_requests.purge') }}
                </button>
            </div>

            <!-- Mobile cards -->
            <div class="sm:hidden space-y-3">
                <p v-if="!accessRequests.parsedAccessRequests.value.items?.length" class="py-8 text-center text-sm text-muted">{{ translate('admin.access_requests.empty') }}</p>
                <div v-for="accessRequest in accessRequests.parsedAccessRequests.value.items" :key="accessRequest.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-primary truncate">{{ accessRequest.requesterName ?? '—' }}</p>
                            <p class="text-xs text-secondary truncate">{{ accessRequest.requesterEmail }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full shrink-0" :class="accessRequests.statusBadge[accessRequest.status]">
                            <component :is="accessRequest.status === 'pending' ? Clock : accessRequest.status === 'approved' ? ShieldCheck : X" class="w-3 h-3" :stroke-width="2.5" />
                            {{ accessRequests.statusLabel.value[accessRequest.status] ?? accessRequest.status }}
                        </span>
                    </div>
                    <p v-if="accessRequest.message" class="text-sm text-secondary">{{ accessRequest.message }}</p>
                    <div class="flex items-center justify-between pt-1 border-t border-line">
                        <p class="text-xs text-muted">{{ formatDateShort(accessRequest.createdAt) }} · expire {{ formatDateShort(accessRequest.expiresAt) }}</p>
                        <div v-if="accessRequest.status === 'pending'" class="flex items-center gap-1">
                            <button class="p-1.5 text-muted hover:text-emerald-400 transition-colors rounded" :title="translate('admin.access_requests.approve')" v-on:click="accessRequests.openApproveModal(accessRequest)">
                                <Check class="w-4 h-4" :stroke-width="2" />
                            </button>
                            <button class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" :title="translate('admin.access_requests.reject')" v-on:click="accessRequests.pendingReject.value = accessRequest">
                                <X class="w-4 h-4" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-surface-2 border-b border-line">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-muted">{{ translate('admin.access_requests.requester') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-muted hidden md:table-cell">{{ translate('admin.access_requests.message') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-muted">{{ translate('admin.access_requests.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-muted hidden lg:table-cell">{{ translate('admin.access_requests.date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-muted hidden lg:table-cell">{{ translate('admin.access_requests.expires') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">{{ translate('admin.users.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <tr v-for="accessRequest in accessRequests.parsedAccessRequests.value.items" :key="accessRequest.id" class="hover:bg-surface-2/50 transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-medium text-primary">{{ accessRequest.requesterName ?? '—' }}</p>
                                <p class="text-xs text-secondary">{{ accessRequest.requesterEmail }}</p>
                            </td>
                            <td class="px-4 py-3 max-w-xs hidden md:table-cell">
                                <p class="text-sm text-secondary truncate">{{ accessRequest.message ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full" :class="accessRequests.statusBadge[accessRequest.status]">
                                    <component :is="accessRequest.status === 'pending' ? Clock : accessRequest.status === 'approved' ? ShieldCheck : X" class="w-3 h-3" :stroke-width="2.5" />
                                    {{ accessRequests.statusLabel.value[accessRequest.status] ?? accessRequest.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(accessRequest.createdAt) }}</td>
                            <td class="px-4 py-3 text-sm text-secondary hidden lg:table-cell">{{ formatDateShort(accessRequest.expiresAt) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <template v-if="accessRequest.status === 'pending'">
                                        <button class="p-1.5 text-muted hover:text-emerald-400 transition-colors rounded" :title="translate('admin.access_requests.approve')" v-on:click="accessRequests.openApproveModal(accessRequest)">
                                            <Check class="w-4 h-4" :stroke-width="2" />
                                        </button>
                                        <button class="p-1.5 text-muted hover:text-rose-400 transition-colors rounded" :title="translate('admin.access_requests.reject')" v-on:click="accessRequests.pendingReject.value = accessRequest">
                                            <X class="w-4 h-4" :stroke-width="2" />
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!accessRequests.parsedAccessRequests.value.items?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-muted">{{ translate('admin.access_requests.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Approve modal -->
            <div v-if="accessRequests.pendingApprove.value" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-line rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <p class="text-sm text-primary">{{ translate('admin.access_requests.approveConfirm', { name: accessRequests.pendingApprove.value.requesterName ?? accessRequests.pendingApprove.value.requesterEmail }) }}</p>
                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="accessRequests.pendingApprove.value = null">{{ translate('common.cancel') }}</button>
                        <button class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors" v-on:click="accessRequests.doApproveRequest">{{ translate('admin.access_requests.approve') }}</button>
                    </div>
                </div>
            </div>

            <!-- Reject modal -->
            <div v-if="accessRequests.pendingReject.value" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-line rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <p class="text-sm text-primary">{{ translate('admin.access_requests.rejectConfirm', { name: accessRequests.pendingReject.value.requesterName ?? accessRequests.pendingReject.value.requesterEmail }) }}</p>
                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="accessRequests.pendingReject.value = null">{{ translate('common.cancel') }}</button>
                        <button class="px-3 py-1.5 text-sm bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors" v-on:click="accessRequests.doRejectRequest">{{ translate('admin.access_requests.reject') }}</button>
                    </div>
                </div>
            </div>

            <!-- Purge modal -->
            <div v-if="accessRequests.confirmPurge.value" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
                <div class="bg-surface border border-line rounded-xl p-6 max-w-sm w-full mx-4 space-y-4">
                    <p class="text-sm text-primary">{{ translate('admin.access_requests.purgeConfirm') }}</p>
                    <div class="flex justify-end gap-2">
                        <button class="px-3 py-1.5 text-sm text-secondary hover:text-primary transition-colors" v-on:click="accessRequests.confirmPurge.value = false">{{ translate('common.cancel') }}</button>
                        <button class="px-3 py-1.5 text-sm bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition-colors" v-on:click="accessRequests.doPurge">{{ translate('admin.access_requests.purge') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
