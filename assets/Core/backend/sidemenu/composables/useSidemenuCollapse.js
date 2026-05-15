import { ref } from "vue";

const SIDEMENU_KEY = "aurora-sidemenu";

export function useSidemenuCollapse() {
    function collapse() {
        document.documentElement.classList.add("sidemenu-collapsed");
        localStorage.setItem(SIDEMENU_KEY, "collapsed");
    }

    function expand() {
        document.documentElement.classList.remove("sidemenu-collapsed");
        localStorage.setItem(SIDEMENU_KEY, "expanded");
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
