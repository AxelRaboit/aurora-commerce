import "./css/sidebar.css";
import { mountApp } from "@/utils/mountApp.js";
import AppSidebar from "@/components/AppSidebar.vue";

mountApp("app-sidebar", AppSidebar, (data) => ({
    userName: data.userName || "",
    userEmail: data.userEmail || "",
    activeRoute: data.activeRoute || "",
    logoutCsrf: data.logoutCsrf || "",
    dashboardPath: data.dashboardPath || "/admin",
    postsPath: data.postsPath || "/admin/posts",
    postTypesPath: data.postTypesPath || "/admin/post-types",
    mediaPath: data.mediaPath || "/admin/media",
    menusPath: data.menusPath || "/admin/menus",
    tagsPath: data.tagsPath || "/admin/tags",
    administrationPath: data.administrationPath || "/dev",
    profilePath: data.profilePath || "/admin/profile",
    logoutPath: data.logoutPath || "/admin/logout",
    locale: data.locale || "fr",
    isDev: data.isDev === "true",
    appVersion: data.appVersion || "",
}));
