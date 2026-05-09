<script setup>
import { useI18n } from "vue-i18n";
import { useUrlSyncedState } from "@/shared/composables/list/useUrlSyncedState.js";
import AppTab from "@/shared/components/nav/AppTab.vue";
import DashboardOverview from "@core/backend/dashboard/DashboardOverview.vue";
import AdminParametersTab from "@core/backend/dev/AdminParametersTab.vue";
import AdminUsersTab from "@core/backend/dev/AdminUsersTab.vue";
import AdminAccessRequestsTab from "@core/backend/dev/AdminAccessRequestsTab.vue";
import AdminAuditTab from "@core/backend/dev/AdminAuditTab.vue";
import AdminPermissionsTab from "@core/backend/dev/AdminPermissionsTab.vue";
import AdminModulesTab from "@core/backend/dev/AdminModulesTab.vue";
import {
    LayoutDashboard,
    Sliders,
    Users,
    KeyRound,
    ScrollText,
    ShieldCheck,
    Puzzle,
} from "lucide-vue-next";

const { t } = useI18n();

const props = defineProps({
    tab: { type: String, default: "overview" },
    stats: { type: Object, default: () => ({}) },
    parameters: { type: Object, default: () => ({}) },
    users: { type: Object, default: () => ({}) },
    accessRequests: { type: Object, default: () => ({}) },
    audit: { type: Object, default: () => ({}) },
    permissions: { type: Object, default: () => ({}) },
    modules: { type: Object, default: () => ({}) },
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
    modulesPath: { type: String, required: true },
    moduleUpdatePath: { type: String, required: true },
    moduleVerifyPasswordPath: { type: String, required: true },
    accessRequestApprovePath: { type: String, required: true },
    accessRequestRejectPath: { type: String, required: true },
    accessRequestPurgePath: { type: String, required: true },
    csrfToken: { type: String, default: "" },
});

// ── Tab state + URL sync ─────────────────────────────────────────────────────
const ROUTE_BY_TAB = {
    overview: () => props.overviewPath,
    parameters: () => props.parametersPath,
    users: () => props.usersPath,
    access_requests: () => props.accessRequestsPath,
    audit: () => props.auditPath,
    permissions: () => props.permissionsPath,
    modules: () => props.modulesPath,
};

const tabs = [
    { key: "overview", label: () => t("backend.tabs.overview"), icon: LayoutDashboard },
    { key: "parameters", label: () => t("backend.tabs.parameters"), icon: Sliders },
    { key: "users", label: () => t("backend.tabs.users"), icon: Users },
    { key: "access_requests", label: () => t("backend.tabs.access_requests"), icon: KeyRound },
    { key: "audit", label: () => t("backend.tabs.audit"), icon: ScrollText },
    { key: "permissions", label: () => t("backend.tabs.permissions"), icon: ShieldCheck },
    { key: "modules", label: () => t("backend.tabs.modules"), icon: Puzzle },
];

const { state: tab, set: setTab } = useUrlSyncedState({
    initial: props.tab,
    serialize: (next) => ROUTE_BY_TAB[next]?.() ?? null,
    deserialize: (event) => event.state?.value ?? props.tab,
});

// Each tab subcomponent owns its data via its own composable. The parent only
// passes initial SSR data for the active tab; non-active tabs receive null and
// their composable auto-loads via XHR on first mount. <KeepAlive> preserves
// state across tab switches so we don't refetch every time.
function initialDataFor(key) {
    if (key !== props.tab) return null;
    return {
        overview: props.stats,
        parameters: props.parameters,
        users: props.users,
        access_requests: props.accessRequests,
        audit: props.audit,
        permissions: props.permissions,
        modules: props.modules,
    }[key] ?? null;
}
</script>

<template>
    <div class="flex flex-col md:flex-row gap-6">
        <nav class="hidden md:flex flex-col w-44 shrink-0 gap-0.5">
            <AppTab
                v-for="tabItem in tabs"
                :key="tabItem.key"
                :active="tab === tabItem.key"
                v-on:click="setTab(tabItem.key)"
            >
                <component :is="tabItem.icon" class="w-4 h-4 shrink-0" :stroke-width="2" />
                {{ tabItem.label() }}
            </AppTab>
        </nav>

        <div class="flex md:hidden gap-1 flex-wrap w-full">
            <AppTab
                v-for="tabItem in tabs"
                :key="tabItem.key"
                :active="tab === tabItem.key"
                size="sm"
                v-on:click="setTab(tabItem.key)"
            >
                <component :is="tabItem.icon" class="w-4 h-4" :stroke-width="2" />
                {{ tabItem.label() }}
            </AppTab>
        </div>

        <div class="flex-1 min-w-0 space-y-6">
            <KeepAlive>
                <DashboardOverview
                    v-if="tab === 'overview'"
                    :stats="initialDataFor('overview') ?? {}"
                />
                <AdminParametersTab
                    v-else-if="tab === 'parameters'"
                    :parameters-path="parametersPath"
                    :parameter-update-path="parameterUpdatePath"
                    :initial-data="initialDataFor('parameters')"
                    :initial-search="search"
                />
                <AdminUsersTab
                    v-else-if="tab === 'users'"
                    :users-path="usersPath"
                    :user-create-path="userCreatePath"
                    :user-update-path="userUpdatePath"
                    :user-toggle-role-path="userToggleRolePath"
                    :user-delete-path="userDeletePath"
                    :impersonate-path="impersonatePath"
                    :csrf-token="csrfToken"
                    :initial-data="initialDataFor('users')"
                    :initial-search="search"
                />
                <AdminAccessRequestsTab
                    v-else-if="tab === 'access_requests'"
                    :access-requests-path="accessRequestsPath"
                    :access-request-approve-path="accessRequestApprovePath"
                    :access-request-reject-path="accessRequestRejectPath"
                    :access-request-purge-path="accessRequestPurgePath"
                    :csrf-token="csrfToken"
                    :initial-data="initialDataFor('access_requests')"
                    :initial-search="search"
                />
                <AdminAuditTab
                    v-else-if="tab === 'audit'"
                    :audit-path="auditPath"
                    :initial-data="initialDataFor('audit')"
                />
                <AdminPermissionsTab
                    v-else-if="tab === 'permissions'"
                    :permissions-path="permissionsPath"
                    :initial-data="initialDataFor('permissions')"
                />
                <AdminModulesTab
                    v-else-if="tab === 'modules'"
                    :modules-path="modulesPath"
                    :module-update-path="moduleUpdatePath"
                    :module-verify-password-path="moduleVerifyPasswordPath"
                    :initial-data="initialDataFor('modules')"
                />
            </KeepAlive>
        </div>
    </div>
</template>
