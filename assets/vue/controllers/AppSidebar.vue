<script setup>
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useTheme } from "@/composables/useTheme.js";
import AppLogo from "@/components/AppLogo.vue";
import AppButton from "@/components/AppButton.vue";
import "@/css/sidebar.css";
import {
    LayoutDashboard,
    FileText,
    Layers,
    Image,
    Menu,
    Tags as TagsIcon,
    Shield,
    LogOut,
    Moon,
    Sun,
    User,
    ChevronsLeft,
    ChevronsRight,
    Menu as MenuIcon,
    X,
} from "lucide-vue-next";

const props = defineProps({
    userName: { type: String, default: "" },
    userEmail: { type: String, default: "" },
    activeRoute: { type: String, default: "" },
    logoutCsrf: { type: String, default: "" },
    dashboardPath: { type: String, default: "/admin" },
    postsPath: { type: String, default: "/admin/posts" },
    postTypesPath: { type: String, default: "/admin/post-types" },
    mediaPath: { type: String, default: "/admin/media" },
    menusPath: { type: String, default: "/admin/menus" },
    taxonomiesPath: { type: String, default: "/admin/taxonomies" },
    administrationPath: { type: String, default: "/dev/dashboard" },
    profilePath: { type: String, default: "/admin/profile" },
    logoutPath: { type: String, default: "/logout" },
    locale: { type: String, default: "fr" },
    isDev: { type: Boolean, default: false },
    appVersion: { type: String, default: "" },
});

const { t } = useI18n();
const { theme, toggle: toggleTheme } = useTheme();

const userInitial = computed(() => props.userName?.charAt(0)?.toUpperCase() || "?");

const SIDEBAR_KEY = "velox-sidebar";

function collapse() {
    document.documentElement.classList.add("sidebar-collapsed");
    localStorage.setItem(SIDEBAR_KEY, "collapsed");
}
function expand() {
    document.documentElement.classList.remove("sidebar-collapsed");
    localStorage.setItem(SIDEBAR_KEY, "expanded");
}

const mobileOpen = ref(false);
function openMobile() { mobileOpen.value = true; document.body.style.overflow = "hidden"; }
function closeMobile() { mobileOpen.value = false; document.body.style.overflow = ""; }

const navItems = [
    { route: "admin_dashboard", path: props.dashboardPath, label: t("nav.dashboard"), icon: LayoutDashboard, activeColor: "indigo" },
    { route: "admin_posts", path: props.postsPath, label: t("nav.posts"), icon: FileText, activeColor: "indigo" },
    { route: "admin_post_types", path: props.postTypesPath, label: t("nav.postTypes"), icon: Layers, activeColor: "indigo" },
    { route: "admin_media", path: props.mediaPath, label: t("nav.media"), icon: Image, activeColor: "indigo" },
    { route: "admin_menus", path: props.menusPath, label: t("nav.menus"), icon: Menu, activeColor: "indigo" },
    { route: "admin_taxonomies", path: props.taxonomiesPath, label: t("nav.taxonomies"), icon: TagsIcon, activeColor: "indigo" },
    ...(props.isDev ? [{ route: "dev_", path: props.administrationPath, label: t("nav.administration"), icon: Shield, activeColor: "rose" }] : []),
];

function itemClasses(item) {
    if (!isActive(item.route)) return "text-secondary hover:text-primary hover:bg-surface-2";
    return item.activeColor === "rose" ? "bg-rose-600/15 text-rose-400" : "bg-indigo-600/15 text-indigo-400";
}

function iconClasses(item) {
    if (!isActive(item.route)) return "text-muted";
    return item.activeColor === "rose" ? "text-rose-400" : "text-indigo-400";
}

function isActive(route) {
    return props.activeRoute?.startsWith(route);
}
</script>

<template>
    <aside id="sidebar" class="hidden lg:flex flex-col fixed inset-y-0 left-0 bg-surface border-r border-line z-30 overflow-hidden">
        <div class="sh-wrap flex items-center h-16 border-b border-line shrink-0 transition-all duration-200">
            <a :href="dashboardPath" class="sh-logo-expanded flex items-center gap-2.5 min-w-0">
                <AppLogo :size="32" class="shrink-0" />
                <div class="flex flex-col min-w-0">
                    <span class="text-primary font-bold text-lg tracking-tight truncate leading-tight">Velox</span>
                    <span v-if="appVersion" class="text-xs text-muted/50 leading-none">{{ appVersion }}</span>
                </div>
            </a>
            <a :href="dashboardPath" class="sh-logo-collapsed">
                <AppLogo :size="32" />
            </a>
            <button
                class="sh-collapse-btn ml-2 p-1.5 rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors shrink-0"
                v-on:click="collapse"
            >
                <ChevronsLeft class="w-4 h-4" />
            </button>
        </div>

        <div class="sh-logo-expanded items-center gap-3 border-b border-line px-4 py-3 shrink-0">
            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-semibold shrink-0">
                {{ userInitial }}
            </div>
            <div class="flex flex-col min-w-0">
                <p class="text-sm font-medium text-primary truncate">{{ userName }}</p>
                <p class="text-xs text-muted truncate">{{ userEmail }}</p>
            </div>
        </div>

        <nav class="sidebar-nav flex-1 py-4 space-y-0.5">
            <a
                v-for="item in navItems"
                :key="item.route"
                :href="item.path"
                class="si flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                :class="itemClasses(item)"
            >
                <component :is="item.icon" class="w-5 h-5 shrink-0" :class="iconClasses(item)" :stroke-width="2" />
                <span class="si-label truncate">{{ item.label }}</span>
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    {{ item.label }}
                </span>
            </a>
        </nav>

        <div class="sidebar-bottom shrink-0 border-t border-line py-3 space-y-0.5">
            <button
                class="sh-expand-btn w-full items-center justify-center py-2.5 rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors"
                v-on:click="expand"
            >
                <ChevronsRight class="w-4 h-4" />
            </button>

            <button
                class="si flex items-center rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors w-full group relative"
                v-on:click="toggleTheme"
            >
                <Moon v-if="theme !== 'dark'" class="w-5 h-5 shrink-0 text-muted" :stroke-width="2" />
                <Sun v-else class="w-5 h-5 shrink-0 text-muted" :stroke-width="2" />
                <span class="si-label">{{ theme === "dark" ? t("nav.lightMode") : t("nav.darkMode") }}</span>
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    {{ theme === "dark" ? t("nav.lightMode") : t("nav.darkMode") }}
                </span>
            </button>

            <a
                :href="profilePath"
                class="si flex items-center rounded-lg text-sm font-medium transition-colors group relative"
                :class="isActive('profile') ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
            >
                <User class="w-5 h-5 shrink-0 text-muted" :stroke-width="2" />
                <span class="si-label truncate">{{ t("nav.profile") }}</span>
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    {{ t("nav.profile") }}
                </span>
            </a>

            <form :action="logoutPath" method="POST">
                <input type="hidden" name="_token" :value="logoutCsrf">
                <button
                    type="submit"
                    class="si flex items-center rounded-lg text-sm font-medium text-secondary hover:text-rose-400 hover:bg-rose-500/10 transition-colors w-full group relative"
                >
                    <LogOut class="w-5 h-5 shrink-0 text-muted group-hover:text-rose-400 transition-colors" :stroke-width="2" />
                    <span class="si-label">{{ t("nav.logout") }}</span>
                    <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                        {{ t("nav.logout") }}
                    </span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Mobile top bar -->
    <div class="lg:hidden fixed top-0 inset-x-0 h-14 bg-surface border-b border-line z-30 flex items-center justify-between px-4">
        <a :href="dashboardPath" class="flex items-center gap-2">
            <AppLogo :size="28" />
            <span class="text-primary font-bold text-base tracking-tight">Velox</span>
        </a>
        <AppButton variant="ghost" size="none" class="p-2" v-on:click="openMobile">
            <MenuIcon class="w-5 h-5" :stroke-width="2" />
        </AppButton>
    </div>

    <!-- Mobile drawer -->
    <div
        class="lg:hidden fixed inset-0 z-50 transition-opacity duration-200"
        :class="mobileOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
    >
        <div class="absolute inset-0 bg-black/60" v-on:click="closeMobile" />
        <div
            class="relative w-60 max-w-[85vw] bg-surface h-full flex flex-col shadow-2xl transition-transform duration-200"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center justify-between px-4 h-16 border-b border-line shrink-0">
                <div class="flex items-center gap-2.5">
                    <AppLogo :size="32" />
                    <div class="flex flex-col">
                        <span class="text-primary font-bold text-lg tracking-tight">Velox</span>
                        <span v-if="appVersion" class="text-xs text-muted/50 leading-none">{{ appVersion }}</span>
                    </div>
                </div>
                <AppButton variant="ghost" size="none" class="p-1.5" v-on:click="closeMobile">
                    <X class="w-5 h-5" :stroke-width="2" />
                </AppButton>
            </div>

            <nav class="flex-1 overflow-y-auto scrollbar-thin px-3 py-4 space-y-0.5">
                <a
                    v-for="item in navItems"
                    :key="item.route"
                    :href="item.path"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="itemClasses(item)"
                >
                    <component :is="item.icon" class="w-5 h-5 shrink-0" :class="iconClasses(item)" :stroke-width="2" />
                    {{ item.label }}
                </a>
            </nav>

            <div class="shrink-0 border-t border-line px-3 py-3 space-y-1">
                <button
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-primary hover:bg-surface-2 transition-colors"
                    v-on:click="toggleTheme"
                >
                    <Moon v-if="theme !== 'dark'" class="w-5 h-5 text-muted shrink-0" :stroke-width="2" />
                    <Sun v-else class="w-5 h-5 text-muted shrink-0" :stroke-width="2" />
                    {{ theme === "dark" ? t("nav.lightMode") : t("nav.darkMode") }}
                </button>
                <a
                    :href="profilePath"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="isActive('profile') ? 'bg-indigo-600/15 text-indigo-400' : 'text-secondary hover:text-primary hover:bg-surface-2'"
                >
                    <User class="w-5 h-5 shrink-0 text-muted" :stroke-width="2" />
                    {{ t("nav.profile") }}
                </a>
                <form :action="logoutPath" method="POST">
                    <input type="hidden" name="_token" :value="logoutCsrf">
                    <button type="submit" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-secondary hover:text-rose-400 hover:bg-rose-500/10 transition-colors">
                        <LogOut class="w-5 h-5 shrink-0 text-muted" :stroke-width="2" />
                        {{ t("nav.logout") }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
