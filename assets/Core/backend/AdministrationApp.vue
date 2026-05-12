<script setup>
import { useI18n } from "vue-i18n";
import { useUrlSyncedState } from "@/shared/composables/list/useUrlSyncedState.js";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppTooltip from "@/shared/components/overlay/AppTooltip.vue";
import DashboardOverview from "@core/backend/dashboard/DashboardOverview.vue";
import ParametersTab from "@core/backend/dev/parameters/ParametersTab.vue";
import UsersTab from "@core/backend/dev/users/UsersTab.vue";
import AccessRequestsTab from "@core/backend/dev/access-requests/AccessRequestsTab.vue";
import AuditTab from "@core/backend/dev/audit/AuditTab.vue";
import PermissionsTab from "@core/backend/dev/permissions/PermissionsTab.vue";
import ModulesTab from "@core/backend/dev/modules/ModulesTab.vue";
import MountPointsTab from "@core/backend/dev/mount-points/MountPointsTab.vue";
import {
    LayoutDashboard,
    Sliders,
    Users,
    KeyRound,
    ScrollText,
    ShieldCheck,
    Puzzle,
    Network,
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
    mountPoints: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    group: { type: String, default: "" },
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
    mountPointsPath: { type: String, required: true },
    mountPointCreatePath: { type: String, required: true },
    mountPointUpdatePath: { type: String, required: true },
    mountPointDeletePath: { type: String, required: true },
    mountPointTestPath: { type: String, required: true },
    accessRequestApprovePath: { type: String, required: true },
    accessRequestRejectPath: { type: String, required: true },
    accessRequestPurgePath: { type: String, required: true },
    csrfToken: { type: String, default: "" },
});

// Each tab is self-describing: label, icon, URL path and initial SSR data colocated.
const tabs = [
    { key: "overview",        label: () => t("backend.tabs.overview"),        description: () => t("backend.tabs.overview_description"),        icon: LayoutDashboard, path: () => props.overviewPath,        initialData: () => props.stats },
    { key: "parameters",      label: () => t("backend.tabs.parameters"),      description: () => t("backend.tabs.parameters_description"),      icon: Sliders,         path: () => props.parametersPath,      initialData: () => props.parameters },
    { key: "users",           label: () => t("backend.tabs.users"),           description: () => t("backend.tabs.users_description"),           icon: Users,           path: () => props.usersPath,           initialData: () => props.users },
    { key: "access_requests", label: () => t("backend.tabs.access_requests"), description: () => t("backend.tabs.access_requests_description"), icon: KeyRound,        path: () => props.accessRequestsPath,  initialData: () => props.accessRequests },
    { key: "audit",           label: () => t("backend.tabs.audit"),           description: () => t("backend.tabs.audit_description"),           icon: ScrollText,      path: () => props.auditPath,           initialData: () => props.audit },
    { key: "permissions",     label: () => t("backend.tabs.permissions"),     description: () => t("backend.tabs.permissions_description"),     icon: ShieldCheck,     path: () => props.permissionsPath,     initialData: () => props.permissions },
    { key: "modules",         label: () => t("backend.tabs.modules"),         description: () => t("backend.tabs.modules_description"),         icon: Puzzle,          path: () => props.modulesPath,         initialData: () => props.modules },
    { key: "mount_points",    label: () => t("backend.tabs.mount_points"),    description: () => t("backend.tabs.mount_points_description"),    icon: Network,         path: () => props.mountPointsPath,     initialData: () => props.mountPoints },
];

const { state: tab, set: setTab } = useUrlSyncedState({
    initial: props.tab,
    serialize: (next) => tabs.find((t) => t.key === next)?.path?.() ?? null,
    deserialize: (event) => event.state?.value ?? props.tab,
});

// Each tab subcomponent owns its data via its own composable. The parent only
// passes initial SSR data for the active tab; non-active tabs receive null and
// their composable auto-loads via XHR on first mount. <KeepAlive> preserves
// state across tab switches so we don't refetch every time.
function initialDataFor(key) {
    if (key !== props.tab) return null;
    return tabs.find((t) => t.key === key)?.initialData?.() ?? null;
}
</script>

<template>
    <div class="flex flex-col md:flex-row gap-6">
        <nav class="hidden md:flex flex-col w-44 shrink-0 gap-0.5">
            <AppTooltip
                v-for="tabItem in tabs"
                :key="tabItem.key"
                :title="tabItem.label()"
                :description="tabItem.description()"
                placement="right"
            >
                <AppTab
                    :active="tab === tabItem.key"
                    v-on:click="setTab(tabItem.key)"
                >
                    <component :is="tabItem.icon" class="w-4 h-4 shrink-0" :stroke-width="2" />
                    {{ tabItem.label() }}
                </AppTab>
            </AppTooltip>
        </nav>

        <div class="flex md:hidden gap-1 flex-wrap w-full">
            <AppTooltip
                v-for="tabItem in tabs"
                :key="tabItem.key"
                :title="tabItem.label()"
                :description="tabItem.description()"
                placement="bottom"
            >
                <AppTab
                    :active="tab === tabItem.key"
                    size="sm"
                    v-on:click="setTab(tabItem.key)"
                >
                    <component :is="tabItem.icon" class="w-4 h-4" :stroke-width="2" />
                    {{ tabItem.label() }}
                </AppTab>
            </AppTooltip>
        </div>

        <div class="flex-1 min-w-0 space-y-6">
            <KeepAlive>
                <DashboardOverview
                    v-if="tab === 'overview'"
                    :stats="initialDataFor('overview') ?? {}"
                />
                <ParametersTab
                    v-else-if="tab === 'parameters'"
                    :parameters-path="parametersPath"
                    :parameter-update-path="parameterUpdatePath"
                    :initial-data="initialDataFor('parameters')"
                    :initial-search="search"
                    :initial-group="group"
                />
                <UsersTab
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
                <AccessRequestsTab
                    v-else-if="tab === 'access_requests'"
                    :access-requests-path="accessRequestsPath"
                    :access-request-approve-path="accessRequestApprovePath"
                    :access-request-reject-path="accessRequestRejectPath"
                    :access-request-purge-path="accessRequestPurgePath"
                    :csrf-token="csrfToken"
                    :initial-data="initialDataFor('access_requests')"
                    :initial-search="search"
                />
                <AuditTab
                    v-else-if="tab === 'audit'"
                    :audit-path="auditPath"
                    :initial-data="initialDataFor('audit')"
                />
                <PermissionsTab
                    v-else-if="tab === 'permissions'"
                    :permissions-path="permissionsPath"
                    :initial-data="initialDataFor('permissions')"
                />
                <ModulesTab
                    v-else-if="tab === 'modules'"
                    :modules-path="modulesPath"
                    :module-update-path="moduleUpdatePath"
                    :module-verify-password-path="moduleVerifyPasswordPath"
                    :initial-data="initialDataFor('modules')"
                />
                <MountPointsTab
                    v-else-if="tab === 'mount_points'"
                    :mount-points-path="mountPointsPath"
                    :mount-point-create-path="mountPointCreatePath"
                    :mount-point-update-path="mountPointUpdatePath"
                    :mount-point-delete-path="mountPointDeletePath"
                    :mount-point-test-path="mountPointTestPath"
                    :initial-data="initialDataFor('mount_points')"
                />
            </KeepAlive>
        </div>
    </div>
</template>
