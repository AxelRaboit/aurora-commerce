<script setup>
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { ref, reactive, computed, onMounted, watch } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { usePaginatedFetch } from "@/shared/composables/api/usePaginatedFetch.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { UserStatus } from "@core/utils/enums/user/userStatus.js";
import { UserPlus, Save, Upload, Trash2 } from "lucide-vue-next";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppFileInput from "@/shared/components/form/AppFileInput.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import UserRowActions from "@core/admin/users/UserRowActions.vue";
import AppAvatar from "@/shared/components/display/AppAvatar.vue";

const { t } = useI18n();
const { formatDate, formatDateShort } = useDateFormat();

const props = defineProps({
    roles: { type: Array, default: () => [] },
    isDev: { type: Boolean, default: false },
    currentUserPriority: { type: Number, default: 0 },
    listPath: { type: String, required: true },
    invitePath: { type: String, required: true },
    updatePath: { type: String, required: true },
    resendInvitationPath: { type: String, required: true },
    toggleDisabledPath: { type: String, required: true },
    impersonatePath: { type: String, default: "" },
    deletePath: { type: String, required: true },
    photoUploadPath: { type: String, required: true },
    photoDeletePath: { type: String, required: true },
    selectablePath: { type: String, required: true },
    showPath: { type: String, required: true },
    currentUserId: { type: Number, default: 0 },
});

const selectableUsers = ref([]);

async function loadSelectableUsers() {
    if (selectableUsers.value.length) return;
    try {
        const response = await fetch(props.selectablePath);
        const data = await response.json();
        selectableUsers.value = data.success ? data.items : [];
    } catch {
        selectableUsers.value = [];
    }
}

const search = ref("");
const roleFilter = ref("");

const { items: users, loading, page, totalPages, total, load: fetchUsers, goToPage, reset: resetUsers } = usePaginatedFetch(
    () => props.listPath,
    () => ({
        ...(search.value && { search: search.value }),
        ...(roleFilter.value && { role: roleFilter.value }),
    }),
);

onMounted(fetchUsers);

watch([search, roleFilter], useDebounce(resetUsers, 300));

// ── Invite modal ─────────────────────────────────────────────────────────────
const inviteModal = reactive({ open: false, errors: {}, saving: false });
const inviteForm = reactive({
    name: "",
    email: "",
    role: props.roles[0]?.value ?? "",
    message: "",
});

function openInvite() {
    inviteModal.errors = {};
    inviteForm.name = "";
    inviteForm.email = "";
    inviteForm.role = props.roles[0]?.value ?? "";
    inviteForm.message = "";
    inviteModal.open = true;
}

async function submitInvite() {
    inviteModal.saving = true;
    inviteModal.errors = {};
    try {
        const response = await fetch(props.invitePath, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(inviteForm),
        });
        const data = await response.json();
        if (!data.success) {
            inviteModal.errors = data.errors ?? {};
            return;
        }
        toast.success(t("admin.users.invitationSent"));
        inviteModal.open = false;
        fetchUsers();
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        inviteModal.saving = false;
    }
}

// ── View modal ───────────────────────────────────────────────────────────────
const viewingUser = ref(null);
async function openView(user) {
    // Show the row data immediately, then enrich with subordinates from the detail endpoint.
    viewingUser.value = { ...user, subordinates: [], subordinatesCount: 0 };
    try {
        const response = await fetch(buildPath(props.showPath, { id: user.id }));
        const data = await response.json();
        if (data.success && viewingUser.value?.id === user.id) {
            viewingUser.value = data.user;
        }
    } catch {
        // Fail silent — the basic row data is already visible.
    }
}

// ── Edit modal ───────────────────────────────────────────────────────────────
const editModal = reactive({ open: false, editing: null, errors: {}, saving: false, photoUploading: false });
const editForm = reactive({ name: "", email: "", role: "", password: "", managerId: null });

const managerOptions = computed(() => {
    const editingId = editModal.editing?.id ?? 0;
    return [
        { value: "", label: "—" },
        ...selectableUsers.value
            .filter((u) => u.id !== editingId)
            .map((u) => ({ value: String(u.id), label: u.name })),
    ];
});

function openEdit(user) {
    editModal.editing = user;
    editModal.errors = {};
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.role ?? props.roles[0]?.value ?? "";
    editForm.password = "";
    editForm.managerId = user.managerId ? String(user.managerId) : "";
    editModal.open = true;
    loadSelectableUsers();
}

async function onPhotoSelected(file) {
    if (!file || !editModal.editing) return;
    editModal.photoUploading = true;
    try {
        const formData = new FormData();
        formData.append("photo", file);
        const url = buildPath(props.photoUploadPath, { id: editModal.editing.id });
        const response = await fetch(url, { method: HttpMethod.Post, body: formData });
        const data = await response.json();
        if (!data.success) {
            const message = data.errors?.photo ?? data.error ?? "shared.common.error";
            toast.error(t(message));
            return;
        }
        editModal.editing = data.user;
        toast.success(t("admin.users.photo.uploaded"));
        fetchUsers();
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        editModal.photoUploading = false;
    }
}

async function removePhoto() {
    if (!editModal.editing) return;
    editModal.photoUploading = true;
    try {
        const url = buildPath(props.photoDeletePath, { id: editModal.editing.id });
        const response = await fetch(url, { method: HttpMethod.Post });
        const data = await response.json();
        if (!data.success) {
            toast.error(t(data.error ?? "shared.common.error"));
            return;
        }
        editModal.editing = data.user;
        toast.success(t("admin.users.photo.removed"));
        fetchUsers();
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        editModal.photoUploading = false;
    }
}

async function submitEdit() {
    if (!editModal.editing) return;
    editModal.saving = true;
    editModal.errors = {};
    try {
        const url = buildPath(props.updatePath, { id: editModal.editing.id });
        const payload = {
            ...editForm,
            managerId: editForm.managerId ? Number(editForm.managerId) : null,
        };
        const response = await fetch(url, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!data.success) {
            editModal.errors = data.errors ?? {};
            return;
        }
        toast.success(t("shared.common.saved"));
        editModal.open = false;
        fetchUsers();
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        editModal.saving = false;
    }
}

// ── Row actions ──────────────────────────────────────────────────────────────
async function resendInvitation(user) {
    try {
        const response = await fetch(buildPath(props.resendInvitationPath, { id: user.id }), { method: HttpMethod.Post });
        const data = await response.json();
        if (data.success) {
            toast.success(t("admin.users.invitationResent"));
            fetchUsers();
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    }
}

const togglingUser = ref(null);
function askToggleDisabled(user) {
    togglingUser.value = user;
}
async function confirmToggleDisabled() {
    const user = togglingUser.value;
    if (!user) return;
    try {
        const response = await fetch(buildPath(props.toggleDisabledPath, { id: user.id }), { method: HttpMethod.Post });
        const data = await response.json();
        if (data.success) {
            toast.success(t("shared.common.saved"));
            fetchUsers();
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        togglingUser.value = null;
    }
}

const deletingUser = ref(null);
async function confirmDelete() {
    const user = deletingUser.value;
    if (!user) return;
    try {
        const response = await fetch(buildPath(props.deletePath, { id: user.id }), { method: HttpMethod.Post });
        const data = await response.json();
        if (data.success) {
            toast.success(t("shared.common.deleted"));
            fetchUsers();
        } else {
            toast.error(t("shared.common.error"));
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        deletingUser.value = null;
    }
}

function statusBadgeColor(status) {
    if ("active" === status) return "emerald";
    if ("invited" === status) return "amber";
    return "rose";
}

const isCurrent = (user) => user.id === props.currentUserId;
const canActOn = (user) => !isCurrent(user) && props.currentUserPriority >= user.rolePriority;
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 max-w-md">
                <AppSearchInput v-model="search" :placeholder="t('admin.users.searchPlaceholder')" />
            </div>
            <AppMultiselect
                v-model="roleFilter"
                :options="roles"
                :placeholder="t('admin.users.allRoles')"
                :allow-empty="true"
                class="sm:max-w-xs"
            />
            <AppButton variant="primary" size="md" class="sm:ml-auto" v-on:click="openInvite">
                <UserPlus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.users.invite') }}
            </AppButton>
        </div>

        <div class="sm:hidden space-y-2">
            <AppNoData v-if="!loading && !users.length" :message="t('admin.users.empty')" />
            <div v-for="user in users" :key="user.id" class="bg-surface border border-line/60 rounded-xl p-4 space-y-3 shadow-sm">
                <div class="flex items-start gap-3">
                    <AppAvatar variant="solid" :name="user.name" :photo-url="user.profilePhotoUrl ?? ''" :size="40" />
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-primary text-sm">
                            {{ user.name }}
                            <AppBadge v-if="isCurrent(user)" color="accent" class="ml-2">{{ t('admin.users.you') }}</AppBadge>
                        </p>
                        <p class="text-xs text-muted mt-0.5">{{ user.email }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <AppBadge :color="statusBadgeColor(user.status)">{{ user.statusLabel }}</AppBadge>
                        <div class="flex items-center gap-1">
                            <AppBadge v-if="user.isDev" color="rose">Dev</AppBadge>
                            <AppBadge v-if="user.roleLabel" color="accent">{{ user.roleLabel }}</AppBadge>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line/40">
                    <p class="text-xs text-muted">{{ formatDateShort(user.createdAt) }}</p>
                    <UserRowActions
                        :user="user"
                        :is-dev="isDev"
                        :can-act="canActOn(user)"
                        :impersonate-path="impersonatePath"
                        v-on:view="openView"
                        v-on:resend="resendInvitation"
                        v-on:edit="openEdit"
                        v-on:toggle-disabled="askToggleDisabled"
                        v-on:delete="deletingUser = $event"
                    />
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line/60 rounded-xl overflow-hidden">
            <AppNoData v-if="!loading && !users.length" :message="t('admin.users.empty')" />
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.users.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('admin.users.email') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.users.role') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.users.status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('admin.users.created') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.users.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="user in users" :key="user.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-4 py-3 text-primary font-medium">
                            <div class="flex items-center gap-3">
                                <AppAvatar variant="solid" :name="user.name" :photo-url="user.profilePhotoUrl ?? ''" :size="32" />
                                <span>
                                    {{ user.name }}
                                    <AppBadge v-if="isCurrent(user)" color="accent" class="ml-2">{{ t('admin.users.you') }}</AppBadge>
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-secondary hidden lg:table-cell">{{ user.email }}</td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <div class="flex items-center gap-1 flex-wrap">
                                <AppBadge v-if="user.isDev" color="rose">Dev</AppBadge>
                                <AppBadge v-if="user.roleLabel" color="accent">{{ user.roleLabel }}</AppBadge>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <AppBadge :color="statusBadgeColor(user.status)">{{ user.statusLabel }}</AppBadge>
                        </td>
                        <td class="px-4 py-3 text-xs text-muted hidden lg:table-cell">{{ formatDateShort(user.createdAt) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <UserRowActions
                                    :user="user"
                                    :is-dev="isDev"
                                    :can-act="canActOn(user)"
                                    :impersonate-path="impersonatePath"
                                    v-on:view="openView"
                                    v-on:resend="resendInvitation"
                                    v-on:edit="openEdit"
                                    v-on:toggle-disabled="askToggleDisabled"
                                    v-on:delete="deletingUser = $event"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

        <AppModal :show="inviteModal.open" max-width="md" v-on:close="inviteModal.open = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.users.invite') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitInvite">
                <AppInput v-model="inviteForm.name" :label="t('admin.users.name')" :placeholder="t('admin.users.namePlaceholder')" :error="inviteModal.errors.name ?? ''" />
                <AppInput
                    v-model="inviteForm.email"
                    :label="t('admin.users.email')"
                    type="email"
                    :placeholder="t('admin.users.emailPlaceholder')"
                    :error="inviteModal.errors.email ?? ''"
                />
                <AppMultiselect
                    v-model="inviteForm.role"
                    :options="roles"
                    :label="t('admin.users.role')"
                    :error="inviteModal.errors.role ?? ''"
                />
                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('admin.users.inviteMessage') }}</label>
                    <textarea
                        v-model="inviteForm.message"
                        rows="3"
                        class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-accent-500 focus:ring-1 focus:ring-accent-500 transition resize-none"
                        :placeholder="t('admin.users.inviteMessagePlaceholder')"
                    />
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="inviteModal.open = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="inviteModal.saving">{{ t('admin.users.sendInvite') }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="!!viewingUser" max-width="md" v-on:close="viewingUser = null">
            <div v-if="viewingUser" class="space-y-5">
                <div class="flex items-center gap-4">
                    <AppAvatar variant="solid" :name="viewingUser.name" :photo-url="viewingUser.profilePhotoUrl ?? ''" :size="64" />
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-primary truncate">{{ viewingUser.name }}</h3>
                        <p class="text-sm text-muted truncate">{{ viewingUser.email }}</p>
                    </div>
                </div>

                <p v-if="viewingUser.moodMessage" class="text-sm text-secondary italic border-l-2 border-accent-500/40 pl-3">
                    “{{ viewingUser.moodMessage }}”
                </p>

                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-secondary uppercase tracking-wide">{{ t('admin.users.status') }}</dt>
                        <dd class="mt-1">
                            <AppBadge :color="statusBadgeColor(viewingUser.status)">{{ viewingUser.statusLabel }}</AppBadge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-secondary uppercase tracking-wide">{{ t('admin.users.role') }}</dt>
                        <dd class="mt-1 flex items-center gap-1 flex-wrap">
                            <AppBadge v-if="viewingUser.isDev" color="rose">Dev</AppBadge>
                            <AppBadge v-if="viewingUser.roleLabel" color="accent">{{ viewingUser.roleLabel }}</AppBadge>
                            <span v-if="!viewingUser.isDev && !viewingUser.roleLabel" class="text-muted">—</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-secondary uppercase tracking-wide">{{ t('admin.users.detail.type') }}</dt>
                        <dd class="mt-1 text-primary">{{ viewingUser.typeLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-secondary uppercase tracking-wide">{{ t('admin.users.detail.locale') }}</dt>
                        <dd class="mt-1 text-primary">{{ t('shared.locales.' + viewingUser.locale) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-secondary uppercase tracking-wide">{{ t('admin.users.detail.createdAt') }}</dt>
                        <dd class="mt-1 text-primary">{{ formatDate(viewingUser.createdAt) }}</dd>
                    </div>
                    <div v-if="viewingUser.invitedAt">
                        <dt class="text-xs text-secondary uppercase tracking-wide">{{ t('admin.users.detail.invitedAt') }}</dt>
                        <dd class="mt-1 text-primary">{{ formatDate(viewingUser.invitedAt) }}</dd>
                    </div>
                </dl>

                <div class="border-t border-line/40 pt-4 space-y-4">
                    <div>
                        <p class="text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('admin.users.manager.label') }}</p>
                        <p v-if="viewingUser.manager" class="text-sm text-primary">{{ viewingUser.manager.name }}</p>
                        <p v-else class="text-sm text-muted">{{ t('admin.users.manager.none') }}</p>
                    </div>
                    <div v-if="viewingUser.subordinates && viewingUser.subordinates.length">
                        <p class="text-xs text-secondary uppercase tracking-wide mb-1.5">
                            {{ t('admin.users.manager.subordinates', { count: viewingUser.subordinatesCount }) }}
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <AppBadge v-for="sub in viewingUser.subordinates" :key="sub.id" color="accent">{{ sub.name }}</AppBadge>
                        </div>
                    </div>
                </div>

                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="viewingUser = null">{{ t('shared.common.close') }}</AppButton>
                </AppModalFooter>
            </div>
        </AppModal>

        <AppModal :show="editModal.open" max-width="md" v-on:close="editModal.open = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.users.edit_title', {name: editModal.editing?.name ?? ''}) }}</h3>

            <div class="flex items-center gap-4 py-4 border-b border-line/40 mb-4">
                <AppAvatar
                    variant="solid"
                    :name="editModal.editing?.name ?? ''"
                    :photo-url="editModal.editing?.profilePhotoUrl ?? ''"
                    :size="64"
                />
                <div class="flex flex-col gap-2">
                    <AppFileInput accept="image/jpeg,image/png,image/webp" v-on:change="onPhotoSelected">
                        <template #default="{ trigger }">
                            <div class="flex items-center gap-2 flex-wrap">
                                <AppButton variant="ghost" size="sm" :loading="editModal.photoUploading" v-on:click="trigger">
                                    <Upload class="w-3.5 h-3.5" :stroke-width="2" />
                                    {{ t('admin.users.photo.upload') }}
                                </AppButton>
                                <AppButton
                                    v-if="editModal.editing?.profilePhotoUrl"
                                    variant="ghost"
                                    size="sm"
                                    :loading="editModal.photoUploading"
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

            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput v-model="editForm.name" :label="t('admin.users.name')" :error="editModal.errors.name ?? ''" />
                <AppInput v-model="editForm.email" :label="t('admin.users.email')" type="email" :error="editModal.errors.email ?? ''" />
                <AppMultiselect
                    v-model="editForm.role"
                    :options="roles"
                    :label="t('admin.users.role')"
                    :allow-empty="false"
                    :error="editModal.errors.role ?? ''"
                />
                <AppMultiselect
                    v-model="editForm.managerId"
                    :options="managerOptions"
                    :label="t('admin.users.manager.label')"
                    :allow-empty="true"
                    :error="editModal.errors.managerId ?? ''"
                />
                <AppInput
                    v-model="editForm.password"
                    :label="t('admin.users.newPassword')"
                    type="password"
                    :placeholder="t('admin.users.newPasswordPlaceholder')"
                    :error="editModal.errors.password ?? ''"
                />
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="editModal.open = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="editModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="!!deletingUser" max-width="sm" v-on:close="deletingUser = null">
            <p class="text-sm text-primary">{{ t('admin.users.deleteConfirm', {name: deletingUser?.name ?? ''}) }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="deletingUser = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>

        <AppModal :show="!!togglingUser" max-width="sm" v-on:close="togglingUser = null">
            <p class="text-sm text-primary">
                {{ t(togglingUser?.status === UserStatus.Disabled ? 'admin.users.enableConfirm' : 'admin.users.disableConfirm', {name: togglingUser?.name ?? ''}) }}
            </p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="togglingUser = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton :variant="togglingUser?.status === UserStatus.Disabled ? 'primary' : 'danger'" size="md" v-on:click="confirmToggleDisabled">
                    {{ t(togglingUser?.status === UserStatus.Disabled ? 'admin.users.enable' : 'admin.users.disable') }}
                </AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
