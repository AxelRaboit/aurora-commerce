<script setup>
import { ref, reactive, computed, onMounted, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Mail, UserPlus, Pencil, Trash2, Power, Search, LogIn } from "lucide-vue-next";
import AppPagination from "@/components/AppPagination.vue";
import AppButton from "@/components/AppButton.vue";
import AppIconButton from "@/components/AppIconButton.vue";
import AppInput from "@/components/AppInput.vue";
import AppSelect from "@/components/AppSelect.vue";
import AppModal from "@/components/AppModal.vue";
import AppNoData from "@/components/AppNoData.vue";

const { t } = useI18n();

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
    currentUserId: { type: Number, default: 0 },
});

const users = ref([]);
const loading = ref(false);
const page = ref(1);
const totalPages = ref(1);
const total = ref(0);
const search = ref("");
const roleFilter = ref("");

async function fetchUsers() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.set("page", String(page.value));
        if (search.value) params.set("search", search.value);
        if (roleFilter.value) params.set("role", roleFilter.value);
        const response = await fetch(`${props.listPath}?${params.toString()}`);
        const data = await response.json();
        if (data.ok) {
            users.value = data.items;
            total.value = data.total;
            totalPages.value = data.totalPages;
            page.value = data.page;
        } else {
            toast.error(t("common.error"));
        }
    } catch {
        toast.error(t("common.error"));
    } finally {
        loading.value = false;
    }
}

onMounted(fetchUsers);

let searchTimeout;
watch([search, roleFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        page.value = 1;
        fetchUsers();
    }, 300);
});

function goToPage(newPage) {
    page.value = newPage;
    fetchUsers();
}

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
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(inviteForm),
        });
        const data = await response.json();
        if (!data.ok) {
            inviteModal.errors = data.errors ?? {};
            return;
        }
        toast.success(t("admin.users.invitationSent"));
        inviteModal.open = false;
        fetchUsers();
    } catch {
        toast.error(t("common.error"));
    } finally {
        inviteModal.saving = false;
    }
}

// ── Edit modal ───────────────────────────────────────────────────────────────
const editModal = reactive({ open: false, editing: null, errors: {}, saving: false });
const editForm = reactive({ name: "", email: "", role: "" });

function openEdit(user) {
    editModal.editing = user;
    editModal.errors = {};
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.role ?? props.roles[0]?.value ?? "";
    editModal.open = true;
}

async function submitEdit() {
    if (!editModal.editing) return;
    editModal.saving = true;
    editModal.errors = {};
    try {
        const url = props.updatePath.replace("__id__", editModal.editing.id);
        const response = await fetch(url, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(editForm),
        });
        const data = await response.json();
        if (!data.ok) {
            editModal.errors = data.errors ?? {};
            return;
        }
        toast.success(t("common.saved"));
        editModal.open = false;
        fetchUsers();
    } catch {
        toast.error(t("common.error"));
    } finally {
        editModal.saving = false;
    }
}

// ── Row actions ──────────────────────────────────────────────────────────────
async function resendInvitation(user) {
    try {
        const response = await fetch(props.resendInvitationPath.replace("__id__", user.id), { method: "POST" });
        const data = await response.json();
        if (data.ok) {
            toast.success(t("admin.users.invitationResent"));
            fetchUsers();
        } else {
            toast.error(t("common.error"));
        }
    } catch {
        toast.error(t("common.error"));
    }
}

async function toggleDisabled(user) {
    try {
        const response = await fetch(props.toggleDisabledPath.replace("__id__", user.id), { method: "POST" });
        const data = await response.json();
        if (data.ok) {
            toast.success(t("common.saved"));
            fetchUsers();
        } else {
            toast.error(t("common.error"));
        }
    } catch {
        toast.error(t("common.error"));
    }
}

const deletingUser = ref(null);
async function confirmDelete() {
    const user = deletingUser.value;
    if (!user) return;
    try {
        const response = await fetch(props.deletePath.replace("__id__", user.id), { method: "DELETE" });
        const data = await response.json();
        if (data.ok) {
            toast.success(t("common.deleted"));
            fetchUsers();
        } else {
            toast.error(t("common.error"));
        }
    } catch {
        toast.error(t("common.error"));
    } finally {
        deletingUser.value = null;
    }
}

function statusClass(status) {
    if ("active" === status) return "bg-emerald-500/15 text-emerald-400";
    if ("invited" === status) return "bg-amber-500/15 text-amber-400";
    return "bg-rose-500/15 text-rose-400";
}

const isCurrent = (user) => user.id === props.currentUserId;
const canActOn = (user) => !isCurrent(user) && props.currentUserPriority >= user.rolePriority;
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="relative flex-1 max-w-md">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" :stroke-width="2" />
                <input
                    v-model="search"
                    type="text"
                    :placeholder="t('admin.users.searchPlaceholder')"
                    class="w-full pl-9 pr-3 py-2 rounded-md border border-line bg-surface text-sm text-primary placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>
            <AppSelect v-model="roleFilter" class="sm:max-w-xs">
                <option value="">{{ t('admin.users.allRoles') }}</option>
                <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </AppSelect>
            <AppButton variant="primary" size="md" class="sm:ml-auto" v-on:click="openInvite">
                <UserPlus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.users.invite') }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
            <AppNoData v-if="!loading && !users.length" :message="t('admin.users.empty')" />
            <table v-else class="w-full text-sm">
                <thead class="bg-surface-2 text-xs text-secondary uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.users.name') }}</th>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.users.email') }}</th>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.users.role') }}</th>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.users.status') }}</th>
                        <th class="text-left px-4 py-3 font-semibold">{{ t('admin.users.created') }}</th>
                        <th class="text-right px-4 py-3 font-semibold">{{ t('admin.users.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id" class="border-t border-line/60 hover:bg-surface-2/50">
                        <td class="px-4 py-3 text-primary font-medium">
                            {{ user.name }}
                            <span v-if="isCurrent(user)" class="ml-2 text-xs text-muted">({{ t('admin.users.you') }})</span>
                        </td>
                        <td class="px-4 py-3 text-secondary">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1 flex-wrap">
                                <span v-if="user.isDev" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-rose-500/15 text-rose-400">Dev</span>
                                <span v-if="user.roleLabel" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-indigo-600/15 text-indigo-400">{{ user.roleLabel }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs" :class="statusClass(user.status)">{{ user.statusLabel }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-muted">{{ new Date(user.createdAt).toLocaleDateString() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="user.status === 'invited' && canActOn(user)" color="amber" :title="t('admin.users.resendInvitation')" v-on:click="resendInvitation(user)">
                                    <Mail class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="isDev && canActOn(user)" color="amber" :title="t('admin.users.impersonate', {name: user.name})" :href="impersonatePath.replace('__email__', encodeURIComponent(user.email))">
                                    <LogIn class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="canActOn(user)" color="indigo" :title="t('common.edit')" v-on:click="openEdit(user)">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="canActOn(user)" color="amber" :title="user.status === 'disabled' ? t('admin.users.enable') : t('admin.users.disable')" v-on:click="toggleDisabled(user)">
                                    <Power class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="canActOn(user)" color="rose" :title="t('common.delete')" v-on:click="deletingUser = user">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />

        <!-- Invite modal -->
        <AppModal :show="inviteModal.open" max-width="md" v-on:close="inviteModal.open = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.users.invite') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitInvite">
                <AppInput v-model="inviteForm.name" :label="t('admin.users.name')" :error="inviteModal.errors.name ?? ''" />
                <AppInput v-model="inviteForm.email" :label="t('admin.users.email')" type="email" :error="inviteModal.errors.email ?? ''" />
                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('admin.users.role') }}</label>
                    <AppSelect v-model="inviteForm.role">
                        <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </AppSelect>
                    <p v-if="inviteModal.errors.role" class="mt-1 text-xs text-rose-500">{{ inviteModal.errors.role }}</p>
                </div>
                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('admin.users.inviteMessage') }}</label>
                    <textarea
                        v-model="inviteForm.message"
                        rows="3"
                        class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition resize-none"
                        :placeholder="t('admin.users.inviteMessagePlaceholder')"
                    />
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="inviteModal.open = false">{{ t('common.cancel') }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="inviteModal.saving">{{ t('admin.users.sendInvite') }}</AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Edit modal -->
        <AppModal :show="editModal.open" max-width="md" v-on:close="editModal.open = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.users.edit_title', {name: editModal.editing?.name ?? ''}) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput v-model="editForm.name" :label="t('admin.users.name')" :error="editModal.errors.name ?? ''" />
                <AppInput v-model="editForm.email" :label="t('admin.users.email')" type="email" :error="editModal.errors.email ?? ''" />
                <div>
                    <label class="block text-xs text-secondary uppercase tracking-wide mb-1.5">{{ t('admin.users.role') }}</label>
                    <AppSelect v-model="editForm.role">
                        <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                    </AppSelect>
                    <p v-if="editModal.errors.role" class="mt-1 text-xs text-rose-500">{{ editModal.errors.role }}</p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <AppButton variant="ghost" size="md" v-on:click="editModal.open = false">{{ t('common.cancel') }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="editModal.saving">{{ t('common.save') }}</AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Delete confirm -->
        <AppModal :show="!!deletingUser" max-width="sm" v-on:close="deletingUser = null">
            <p class="text-sm text-primary">{{ t('admin.users.deleteConfirm', {name: deletingUser?.name ?? ''}) }}</p>
            <div class="flex justify-end gap-2 pt-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingUser = null">{{ t('common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDelete">{{ t('common.delete') }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
