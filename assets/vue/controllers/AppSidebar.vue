<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useDebounce } from "@/composables/useDebounce.js";
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
    Users as UsersIcon,
    Globe,
    Shield,
    LogOut,
    Mail,
    Moon,
    Sun,
    User,
    ChevronsLeft,
    ChevronsRight,
    Menu as MenuIcon,
    X,
    Settings,
    Palette,
    MessageSquare,
    ClipboardList,
    Search,
    Loader2,
} from "lucide-vue-next";
import { statusBadge } from "@/utils/statusStyles.js";

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
    usersPath: { type: String, default: "/admin/users" },
    frontPath: { type: String, default: "/" },
    administrationPath: { type: String, default: "/dev/dashboard" },
    profilePath: { type: String, default: "/admin/profile" },
    logoutPath: { type: String, default: "/logout" },
    locale: { type: String, default: "fr" },
    isAdmin: { type: Boolean, default: false },
    isDev: { type: Boolean, default: false },
    settingsPath: { type: String, default: "" },
    themesPath: { type: String, default: "" },
    commentsPath: { type: String, default: "" },
    formsPath: { type: String, default: "" },
    mailpitUrl: { type: String, default: "" },
    siteLogoUrl: { type: String, default: "" },
    appVersion: { type: String, default: "" },
    searchPath: { type: String, default: "/admin/search" },
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
    ...(props.isAdmin ? [{ route: "admin_users", path: props.usersPath, label: t("nav.users"), icon: UsersIcon, activeColor: "indigo" }] : []),
    ...(props.commentsPath !== "" ? [{ route: "admin_comments", path: props.commentsPath, label: t("nav.comments"), icon: MessageSquare, activeColor: "indigo" }] : []),
    ...(props.formsPath !== "" ? [{ route: "admin_forms", path: props.formsPath, label: t("nav.forms"), icon: ClipboardList, activeColor: "indigo" }] : []),
    ...(props.settingsPath !== "" ? [{ route: "admin_settings", path: props.settingsPath, label: t("nav.settings"), icon: Settings, activeColor: "indigo" }] : []),
    ...(props.themesPath !== "" ? [{ route: "admin_themes", path: props.themesPath, label: t("nav.themes"), icon: Palette, activeColor: "indigo" }] : []),
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
    if ("__front" === route) return false;
    return props.activeRoute?.startsWith(route);
}

// Search palette
const searchOpen = ref(false);
const searchQuery = ref("");
const searchResults = ref({ posts: [], terms: [], media: [] });
const searchLoading = ref(false);
const searchHighlightedIndex = ref(0);
const searchInputRef = ref(null);

const isMac = typeof navigator !== "undefined" && /Mac|iP(hone|od|ad)/.test(navigator.platform);
const modKeyLabel = isMac ? "⌘" : "Ctrl";

const flatResults = computed(() => [
    ...searchResults.value.posts.map((item) => ({ kind: "post", item })),
    ...searchResults.value.terms.map((item) => ({ kind: "term", item })),
    ...searchResults.value.media.map((item) => ({ kind: "media", item })),
]);

const totalResults = computed(() => flatResults.value.length);

function openPalette() {
    searchOpen.value = true;
    searchQuery.value = "";
    searchResults.value = { posts: [], terms: [], media: [] };
    searchHighlightedIndex.value = 0;
    nextTick(() => searchInputRef.value?.focus());
}

function closePalette() {
    searchOpen.value = false;
}

function openSearchFromMobile() {
    closeMobile();
    openPalette();
}

function onGlobalKeydown(event) {
    if ((event.ctrlKey || event.metaKey) && "k" === event.key.toLowerCase()) {
        event.preventDefault();
        searchOpen.value ? closePalette() : openPalette();
        return;
    }
    if (!searchOpen.value) return;
    if ("Escape" === event.key) {
        event.preventDefault();
        closePalette();
    } else if ("ArrowDown" === event.key) {
        event.preventDefault();
        if (totalResults.value) searchHighlightedIndex.value = (searchHighlightedIndex.value + 1) % totalResults.value;
    } else if ("ArrowUp" === event.key) {
        event.preventDefault();
        if (totalResults.value) searchHighlightedIndex.value = (searchHighlightedIndex.value - 1 + totalResults.value) % totalResults.value;
    } else if ("Enter" === event.key) {
        event.preventDefault();
        activateResult(flatResults.value[searchHighlightedIndex.value]);
    }
}

watch(searchQuery, useDebounce(runSearch, 180));

async function runSearch() {
    const trimmed = searchQuery.value.trim();
    if ("" === trimmed) {
        searchResults.value = { posts: [], terms: [], media: [] };
        return;
    }
    searchLoading.value = true;
    try {
        const url = new URL(props.searchPath, window.location.origin);
        url.searchParams.set("q", trimmed);
        const response = await fetch(url);
        if (!response.ok) throw new Error();
        const data = await response.json();
        searchResults.value = {
            posts: data.posts ?? [],
            terms: data.terms ?? [],
            media: data.media ?? [],
        };
        searchHighlightedIndex.value = 0;
    } catch {
        searchResults.value = { posts: [], terms: [], media: [] };
    } finally {
        searchLoading.value = false;
    }
}

function activateResult(entry) {
    if (!entry) return;
    if ("post" === entry.kind) {
        const url = new URL(props.postsPath, window.location.origin);
        if (entry.item.trashed) url.searchParams.set("trashed", "1");
        window.location.href = url.toString();
    } else if ("term" === entry.kind) {
        window.location.href = props.taxonomiesPath;
    } else if ("media" === entry.kind) {
        window.location.href = props.mediaPath;
    }
}

function highlightMatch(text) {
    if (!text || !searchQuery.value) return text ?? "";
    const tokens = searchQuery.value
        .trim()
        .split(/\s+/)
        .filter((token) => token.length > 1)
        .map((token) => token.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));
    if (!tokens.length) return text;
    const regex = new RegExp(`(${tokens.join("|")})`, "ig");
    return text.replace(regex, '<mark class="bg-indigo-400/30 text-primary rounded px-0.5">$1</mark>');
}

function entryIndex(kind, item) {
    return flatResults.value.findIndex((entry) => entry.kind === kind && entry.item.id === item.id);
}

onMounted(() => window.addEventListener("keydown", onGlobalKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", onGlobalKeydown));
</script>

<template>
    <aside id="sidebar" class="hidden lg:flex flex-col fixed inset-y-0 left-0 bg-surface border-r border-line z-30 overflow-hidden">
        <div class="sh-wrap flex items-center h-16 border-b border-line shrink-0 transition-all duration-200">
            <a :href="dashboardPath" class="sh-logo-expanded flex items-center gap-2.5 min-w-0">
                <img v-if="siteLogoUrl" :src="siteLogoUrl" alt="Logo" class="h-8 w-auto shrink-0 object-contain">
                <AppLogo v-else :size="32" class="shrink-0" />
                <div class="flex flex-col min-w-0">
                    <span class="text-primary font-bold text-lg tracking-tight truncate leading-tight">Velox</span>
                    <span v-if="appVersion" class="text-xs text-muted/50 leading-none">{{ appVersion }}</span>
                </div>
            </a>
            <a :href="dashboardPath" class="sh-logo-collapsed">
                <img v-if="siteLogoUrl" :src="siteLogoUrl" alt="Logo" class="h-8 w-auto object-contain">
                <AppLogo v-else :size="32" />
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

        <div class="px-3 py-2 border-b border-line shrink-0">
            <a :href="frontPath" target="_blank" rel="noopener" class="si flex items-center rounded-lg text-sm font-medium text-secondary hover:text-emerald-400 hover:bg-emerald-500/10 transition-colors group relative">
                <Globe class="w-5 h-5 shrink-0 text-muted group-hover:text-emerald-400 transition-colors" :stroke-width="2" />
                <span class="si-label truncate">{{ t("nav.viewSite") }}</span>
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    {{ t("nav.viewSite") }}
                </span>
            </a>
        </div>

        <!-- Search -->
        <div class="sh-search-section px-3 py-2 border-b border-line shrink-0">
            <button
                type="button"
                class="sh-logo-expanded w-full items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-muted border border-line/60 hover:border-line hover:text-primary hover:bg-surface-2 transition-colors"
                v-on:click="openPalette"
            >
                <Search class="w-4 h-4 shrink-0" :stroke-width="2" />
                <span class="flex-1 text-left">{{ t("search.button") }}</span>
                <kbd class="px-1.5 py-0.5 rounded bg-surface-2 border border-line font-mono text-xs shrink-0">{{ modKeyLabel }}+K</kbd>
            </button>
            <button
                type="button"
                class="sh-logo-collapsed si items-center rounded-lg text-muted hover:text-primary hover:bg-surface-2 transition-colors w-full group relative"
                v-on:click="openPalette"
            >
                <Search class="w-5 h-5 shrink-0" :stroke-width="2" />
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    {{ t("search.button") }}
                </span>
            </button>
        </div>

        <nav class="sidebar-nav flex-1 py-4 space-y-0.5">
            <a
                v-for="item in navItems"
                :key="item.route"
                :href="item.path"
                :target="item.external ? '_blank' : undefined"
                :rel="item.external ? 'noopener' : undefined"
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

            <a
                v-if="mailpitUrl"
                :href="mailpitUrl"
                target="_blank"
                rel="noopener"
                class="si flex items-center rounded-lg text-sm font-medium text-secondary hover:text-amber-400 hover:bg-amber-500/10 transition-colors group relative"
            >
                <Mail class="w-5 h-5 shrink-0 text-muted group-hover:text-amber-400 transition-colors" :stroke-width="2" />
                <span class="si-label">Mailpit</span>
                <span class="si-tooltip absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-surface-3 border border-line text-xs font-medium text-primary whitespace-nowrap pointer-events-none z-50 shadow-lg">
                    Mailpit
                </span>
            </a>

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
        <div class="flex items-center gap-1">
            <AppButton variant="ghost" size="none" class="p-2" v-on:click="openPalette">
                <Search class="w-5 h-5" :stroke-width="2" />
            </AppButton>
            <AppButton variant="ghost" size="none" class="p-2" v-on:click="openMobile">
                <MenuIcon class="w-5 h-5" :stroke-width="2" />
            </AppButton>
        </div>
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

            <div class="shrink-0 px-3 pt-3 pb-1 space-y-1">
                <button
                    type="button"
                    class="w-full flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-muted border border-line/60 hover:border-line hover:text-primary hover:bg-surface-2 transition-colors"
                    v-on:click="openSearchFromMobile"
                >
                    <Search class="w-4 h-4 shrink-0" :stroke-width="2" />
                    <span class="flex-1 text-left">{{ t("search.button") }}</span>
                </button>
                <a :href="frontPath" target="_blank" rel="noopener" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-secondary hover:text-emerald-400 hover:bg-emerald-500/10 transition-colors">
                    <Globe class="w-5 h-5 shrink-0 text-muted" :stroke-width="2" />
                    {{ t("nav.viewSite") }}
                </a>
                <hr class="border-line mt-1">
            </div>

            <nav class="flex-1 overflow-y-auto scrollbar-thin px-3 py-2 space-y-0.5">
                <a
                    v-for="item in navItems"
                    :key="item.route"
                    :href="item.path"
                    :target="item.external ? '_blank' : undefined"
                    :rel="item.external ? 'noopener' : undefined"
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

    <!-- Search palette modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="searchOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4" v-on:click.self="closePalette">
                <div class="fixed inset-0 bg-black/60" v-on:click="closePalette" />

                <div class="relative w-full max-w-2xl bg-surface border border-line rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[70vh]">
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-line">
                        <Search class="w-4 h-4 text-muted shrink-0" :stroke-width="2" />
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            :placeholder="t('search.placeholder')"
                            class="flex-1 bg-transparent border-0 outline-none text-primary placeholder-muted text-sm"
                        >
                        <Loader2 v-if="searchLoading" class="w-4 h-4 text-muted animate-spin" :stroke-width="2" />
                        <AppButton variant="ghost" size="none" class="p-1" v-on:click="closePalette">
                            <X class="w-4 h-4" :stroke-width="2" />
                        </AppButton>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <div v-if="!searchQuery.trim()" class="px-4 py-8 text-sm text-muted text-center">
                            {{ t("search.hint") }}
                        </div>
                        <div v-else-if="!searchLoading && totalResults === 0" class="px-4 py-8 text-sm text-muted text-center">
                            {{ t("search.empty") }}
                        </div>

                        <div v-if="searchResults.posts.length" class="px-2 py-2 space-y-1">
                            <p class="px-2 py-1 text-xs uppercase tracking-wide text-muted font-semibold flex items-center gap-1.5">
                                <FileText class="w-3 h-3" :stroke-width="2" />
                                {{ t("search.sections.posts") }}
                            </p>
                            <button
                                v-for="post in searchResults.posts"
                                :key="`post-${post.id}`"
                                type="button"
                                class="w-full text-left px-2 py-2 rounded-md transition-colors flex items-start gap-3"
                                :class="entryIndex('post', post) === searchHighlightedIndex ? 'bg-indigo-600/15 text-indigo-400' : 'hover:bg-surface-2'"
                                v-on:mouseenter="searchHighlightedIndex = entryIndex('post', post)"
                                v-on:click="activateResult({ kind: 'post', item: post })"
                            >
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium shrink-0 mt-0.5" :class="statusBadge(post.status)">
                                    {{ t("admin.stats.postStatus." + post.status) }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-primary truncate" v-html="highlightMatch(post.title ?? '(—)')" />
                                    <div v-if="post.snippet" class="text-xs text-muted line-clamp-2" v-html="highlightMatch(post.snippet)" />
                                    <div class="text-xs text-muted mt-0.5">{{ post.postType }}</div>
                                </div>
                            </button>
                        </div>

                        <div v-if="searchResults.terms.length" class="px-2 py-2 space-y-1 border-t border-line">
                            <p class="px-2 py-1 text-xs uppercase tracking-wide text-muted font-semibold flex items-center gap-1.5">
                                <TagsIcon class="w-3 h-3" :stroke-width="2" />
                                {{ t("search.sections.terms") }}
                            </p>
                            <button
                                v-for="term in searchResults.terms"
                                :key="`term-${term.id}`"
                                type="button"
                                class="w-full text-left px-2 py-2 rounded-md transition-colors flex items-center gap-3"
                                :class="entryIndex('term', term) === searchHighlightedIndex ? 'bg-indigo-600/15 text-indigo-400' : 'hover:bg-surface-2'"
                                v-on:mouseenter="searchHighlightedIndex = entryIndex('term', term)"
                                v-on:click="activateResult({ kind: 'term', item: term })"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-primary truncate" v-html="highlightMatch(term.name ?? '(—)')" />
                                    <div class="text-xs text-muted">{{ term.taxonomy }}</div>
                                </div>
                            </button>
                        </div>

                        <div v-if="searchResults.media.length" class="px-2 py-2 space-y-1 border-t border-line">
                            <p class="px-2 py-1 text-xs uppercase tracking-wide text-muted font-semibold flex items-center gap-1.5">
                                <Image class="w-3 h-3" :stroke-width="2" />
                                {{ t("search.sections.media") }}
                            </p>
                            <button
                                v-for="media in searchResults.media"
                                :key="`media-${media.id}`"
                                type="button"
                                class="w-full text-left px-2 py-2 rounded-md transition-colors flex items-center gap-3"
                                :class="entryIndex('media', media) === searchHighlightedIndex ? 'bg-indigo-600/15 text-indigo-400' : 'hover:bg-surface-2'"
                                v-on:mouseenter="searchHighlightedIndex = entryIndex('media', media)"
                                v-on:click="activateResult({ kind: 'media', item: media })"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-primary truncate" v-html="highlightMatch(media.name ?? '(—)')" />
                                    <div class="text-xs text-muted">{{ media.mimeType }}</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="px-4 py-2 border-t border-line bg-surface-2/50 text-xs text-muted flex items-center gap-4">
                        <span><kbd class="px-1 py-0.5 rounded bg-surface border border-line font-mono text-xs">↑↓</kbd> {{ t("search.keys.navigate") }}</span>
                        <span><kbd class="px-1 py-0.5 rounded bg-surface border border-line font-mono text-xs">Enter</kbd> {{ t("search.keys.select") }}</span>
                        <span class="ml-auto"><kbd class="px-1 py-0.5 rounded bg-surface border border-line font-mono text-xs">Esc</kbd> {{ t("search.keys.close") }}</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
