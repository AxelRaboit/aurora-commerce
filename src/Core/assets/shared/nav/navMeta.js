/**
 * Shared nav metadata: kebab-icon-name → Lucide component, and module-id →
 * theme color. Mirrors the sidemenu's icon map + section palette so other
 * surfaces (e.g. the modules dashboard) render icons and colours the same way.
 */
import {
    LayoutDashboard,
    FileText,
    Layers,
    Image,
    Images,
    Menu,
    Tag,
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
    FolderTree,
    Folder,
    CalendarDays,
    KanbanSquare,
    KeyRound,
    Lock,
    Flame,
    StickyNote,
    Wallet,
    PieChart,
    Target,
    Repeat,
    Sparkles,
    Scale,
    Globe2,
    BarChart3,
    Upload,
    ScrollText,
    ClipboardCheck,
} from "lucide-vue-next";

export const ICON_MAP = {
    "layout-dashboard": LayoutDashboard,
    "file-text": FileText,
    layers: Layers,
    image: Image,
    images: Images,
    menu: Menu,
    tag: Tag,
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
    "folder-tree": FolderTree,
    folder: Folder,
    "calendar-days": CalendarDays,
    "kanban-square": KanbanSquare,
    "key-round": KeyRound,
    vault: Lock,
    flame: Flame,
    "scroll-text": ScrollText,
    "clipboard-check": ClipboardCheck,
    "sticky-note": StickyNote,
    wallet: Wallet,
    "pie-chart": PieChart,
    target: Target,
    repeat: Repeat,
    sparkles: Sparkles,
    scale: Scale,
    "globe-2": Globe2,
    "bar-chart-3": BarChart3,
    upload: Upload,
};

/** Resolve a kebab icon name to its Lucide component (FileText fallback). */
export function resolveNavIcon(name) {
    return ICON_MAP[name] ?? FileText;
}

/**
 * Module id → Tailwind colour family, mirroring the sidemenu section palette
 * (useSidemenuSectionTheme). Used to tint a module's icons consistently.
 */
export const MODULE_COLOR = {
    general: "slate",
    platform: "indigo",
    tools: "stone",
    vault: "stone",
    configuration: "zinc",
    notes: "yellow",
    personal_finance: "emerald",
    editorial: "rose",
    ged: "lime",
    media: "pink",
    planning: "cyan",
    crm: "sky",
    erp: "teal",
    ecommerce: "purple",
    billing: "amber",
    photo: "fuchsia",
    project: "blue",
    hr: "green",
    dev: "orange",
    assistant: "violet",
};

/** Full Tailwind text class per colour family (safelist-friendly literals). */
const ICON_TEXT_CLASS = {
    slate: "text-slate-400",
    indigo: "text-indigo-400",
    stone: "text-stone-400",
    zinc: "text-zinc-400",
    yellow: "text-yellow-400",
    emerald: "text-emerald-400",
    rose: "text-rose-400",
    lime: "text-lime-400",
    pink: "text-pink-400",
    cyan: "text-cyan-400",
    sky: "text-sky-400",
    teal: "text-teal-400",
    purple: "text-purple-400",
    amber: "text-amber-400",
    fuchsia: "text-fuchsia-400",
    blue: "text-blue-400",
    green: "text-green-400",
    orange: "text-orange-400",
    violet: "text-violet-400",
    accent: "text-accent",
};

/** Module id (e.g. 'ecommerce') → its icon text colour class. */
export function moduleIconColorClass(moduleId) {
    return ICON_TEXT_CLASS[MODULE_COLOR[moduleId] ?? "accent"] ?? "text-accent";
}

/** Derive the module id from a toggle key: 'modules_ecommerce_backend' → 'ecommerce'. */
export function moduleIdFromToggleKey(key) {
    return String(key)
        .replace(/^modules_/, "")
        .replace(/_(backend|frontend)$/, "");
}
