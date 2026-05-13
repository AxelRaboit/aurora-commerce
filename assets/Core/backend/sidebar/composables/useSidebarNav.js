import { ref, computed, onMounted, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { usePersistedExpanded } from "@/shared/composables/usePersistedExpanded.js";
import {
    LayoutDashboard,
    FileText,
    Layers,
    Image,
    Images,
    Menu,
    Tags as TagsIcon,
    Users as UsersIcon,
    Building2,
    TrendingUp,
    Shield,
    Settings,
    Palette,
    MessageSquare,
    ClipboardList,
    Package,
    ShoppingBag,
    Map as MapIcon,
    Receipt,
    ScanLine,
    Briefcase,
    ShieldCheck,
    Camera,
    ShoppingCart,
    Gauge,
    FolderKanban,
    FolderOpen,
    Folder,
    CalendarDays,
    KanbanSquare,
    KeyRound,
    Lock,
} from "lucide-vue-next";

const ICON_MAP = {
    "layout-dashboard": LayoutDashboard,
    "file-text": FileText,
    layers: Layers,
    image: Image,
    images: Images,
    menu: Menu,
    tags: TagsIcon,
    users: UsersIcon,
    "building-2": Building2,
    "trending-up": TrendingUp,
    shield: Shield,
    settings: Settings,
    palette: Palette,
    "message-square": MessageSquare,
    "clipboard-list": ClipboardList,
    package: Package,
    "shopping-bag": ShoppingBag,
    map: MapIcon,
    receipt: Receipt,
    "scan-line": ScanLine,
    briefcase: Briefcase,
    "shield-check": ShieldCheck,
    camera: Camera,
    "shopping-cart": ShoppingCart,
    gauge: Gauge,
    "folder-kanban": FolderKanban,
    "folder-open": FolderOpen,
    folder: Folder,
    "calendar-days": CalendarDays,
    "kanban-square": KanbanSquare,
    "key-round": KeyRound,
    vault: Lock,
};

export function useSidebarNav(navSections, activeRoute, sectionAliases = {}) {
    const { t } = useI18n();

    const {
        isExpanded: isGroupExpanded,
        toggle: toggleGroup,
        getRaw: getGroupRaw,
    } = usePersistedExpanded("aurora-sidebar-groups");
    const { isExpanded: isSectionExpandedById, toggle: toggleSectionById } =
        usePersistedExpanded("aurora-sidebar-sections");

    function isSectionExpanded(section) {
        return isSectionExpandedById(section.id);
    }
    function toggleSection(section) {
        toggleSectionById(section.id);
    }

    const dashboardPath = computed(
        () => navSections?.[0]?.items?.[0]?.path ?? "/admin",
    );

    function buildItem(item) {
        return {
            route: item.route,
            path: item.path,
            label: t(item.labelKey),
            description: item.descriptionKey ? t(item.descriptionKey) : "",
            icon: ICON_MAP[item.icon] ?? FileText,
            activeColor: item.activeColor ?? "accent",
            children: (item.children ?? []).map(buildItem),
        };
    }

    const groupedSections = computed(() =>
        navSections.map((section) => ({
            id: section.id,
            label: sectionAliases[section.id]?.trim() || t(`backend.nav.sections.${section.id}`),
            items: section.items.map(buildItem),
        })),
    );

    const navItems = computed(() =>
        groupedSections.value.flatMap((s) =>
            s.items.flatMap((i) => [i, ...(i.children ?? [])]),
        ),
    );

    const navFilter = ref("");

    const displayedSections = computed(() => {
        const q = navFilter.value.trim().toLowerCase();
        if (!q) return groupedSections.value;
        const results = [];
        for (const section of groupedSections.value) {
            const matchingItems = [];
            for (const item of section.items) {
                if (item.label.toLowerCase().includes(q)) {
                    matchingItems.push(item);
                } else if (item.children?.length) {
                    const matchingChildren = item.children.filter((c) =>
                        c.label.toLowerCase().includes(q),
                    );
                    matchingItems.push(...matchingChildren);
                }
            }
            if (matchingItems.length)
                results.push({ ...section, items: matchingItems });
        }
        return results;
    });

    function isActive(route) {
        return activeRoute?.startsWith(route);
    }

    function itemIsActive(item) {
        return (
            isActive(item.route) ||
            (item.children?.some((child) => isActive(child.route)) ?? false)
        );
    }

    function itemClasses(item) {
        if (isActive(item.route)) {
            return item.activeColor === "rose"
                ? "bg-rose-600/15 text-rose-400"
                : "bg-accent-600/15 text-accent-400";
        }
        if (itemIsActive(item)) {
            return item.activeColor === "rose"
                ? "text-rose-400 hover:bg-rose-600/10"
                : "text-accent-400 hover:bg-surface-2";
        }
        return item.activeColor === "rose"
            ? "text-secondary hover:text-rose-400 hover:bg-rose-600/10"
            : "text-secondary hover:text-accent-400 hover:bg-accent-600/10";
    }

    function iconClasses(item) {
        if (isActive(item.route) || itemIsActive(item)) {
            return item.activeColor === "rose"
                ? "text-rose-400"
                : "text-accent-400";
        }
        return item.activeColor === "rose"
            ? "text-muted group-hover:text-rose-400 transition-colors"
            : "text-muted";
    }

    onMounted(() => {
        groupedSections.value.forEach((section) => {
            section.items.forEach((item) => {
                if (
                    item.children?.length &&
                    getGroupRaw(item.route) === undefined &&
                    item.children.some((c) => isActive(c.route))
                ) {
                    toggleGroup(item.route);
                }
            });
        });
    });

    onMounted(() =>
        nextTick(() => {
            const active = document.querySelector(
                ".sidebar-nav [data-sidebar-active='true']",
            );
            active?.scrollIntoView({ block: "nearest", behavior: "instant" });
        }),
    );

    return {
        dashboardPath,
        groupedSections,
        navItems,
        navFilter,
        displayedSections,
        isGroupExpanded,
        toggleGroup,
        isSectionExpanded,
        toggleSection,
        isActive,
        itemIsActive,
        itemClasses,
        iconClasses,
    };
}
