import { ref } from "vue";

const SIDEBAR_KEY = "aurora-sidebar";

export function useSidebarCollapse() {
    function collapse() {
        document.documentElement.classList.add("sidebar-collapsed");
        localStorage.setItem(SIDEBAR_KEY, "collapsed");
    }

    function expand() {
        document.documentElement.classList.remove("sidebar-collapsed");
        localStorage.setItem(SIDEBAR_KEY, "expanded");
    }

    const mobileOpen = ref(false);

    function openMobile() {
        mobileOpen.value = true;
        document.body.style.overflow = "hidden";
    }

    function closeMobile() {
        mobileOpen.value = false;
        document.body.style.overflow = "";
    }

    return { collapse, expand, mobileOpen, openMobile, closeMobile };
}
