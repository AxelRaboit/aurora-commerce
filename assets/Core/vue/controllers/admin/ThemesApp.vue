<script setup>
import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref, reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Palette, Check, Pencil, Trash2, Plus, Save, } from "lucide-vue-next";
import AppButton from "@/shared/components/AppButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppTextarea from "@/shared/components/AppTextarea.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppModalFooter from "@/shared/components/AppModalFooter.vue";
import AppBadge from "@/shared/components/AppBadge.vue";

const { t } = useI18n();

const props = defineProps({
    themes: { type: Array, default: () => [] },
    activatePath: { type: String, default: "" },
    updatePath: { type: String, default: "" },
    createPath: { type: String, default: "" },
    deletePath: { type: String, default: "" },
});

const themeList = ref(props.themes.map((theme) => ({ ...theme })));

function accentColor(theme) {
    // Prefer the new primary_color field, fall back to the legacy --th-accent CSS var,
    // then to the default accent hue if neither is set.
    return theme.config?.["primary_color"] ?? theme.config?.["--th-accent"] ?? "#6366f1";
}

// ── Activate ─────────────────────────────────────────────────────────────────
async function activateTheme(theme) {
    try {
        const url = props.activatePath.replace("__id__", theme.id);
        const response = await fetch(url, { method: HttpMethod.Post });
        const data = await response.json();
        if (!data.ok) {
            toast.error(t("shared.common.error"));
            return;
        }
        themeList.value = themeList.value.map((item) => ({
            ...item,
            active: item.id === theme.id,
        }));
        toast.success(t("admin.themes.activated"));
    } catch {
        toast.error(t("shared.common.error"));
    }
}

// ── Create modal ─────────────────────────────────────────────────────────────
const createModal = reactive({ open: false, saving: false, errors: {} });
const createForm = reactive({ name: "", slug: "", description: "" });

function openCreate() {
    createModal.errors = {};
    createForm.name = "";
    createForm.slug = "";
    createForm.description = "";
    createModal.open = true;
}

async function submitCreate() {
    createModal.saving = true;
    createModal.errors = {};
    try {
        const response = await fetch(props.createPath, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(createForm),
        });
        const data = await response.json();
        if (!data.ok) {
            createModal.errors = data.errors ?? {};
            return;
        }
        themeList.value.push(data.theme);
        createModal.open = false;
        toast.success(t("admin.themes.created"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        createModal.saving = false;
    }
}

// ── Edit modal ────────────────────────────────────────────────────────────────
const CSS_SECTIONS = computed(() => [
    {
        key: "general",
        label: t("admin.themes.sections.general"),
        vars: [
            { key: "--th-accent",       label: t("admin.themes.vars.accent") },
            { key: "--th-accent-hover", label: t("admin.themes.vars.accentHover") },
            { key: "--th-bg",           label: t("admin.themes.vars.bg") },
            { key: "--th-surface",      label: t("admin.themes.vars.surface") },
            { key: "--th-surface-2",    label: t("admin.themes.vars.surface2") },
            { key: "--th-primary",      label: t("admin.themes.vars.primary") },
            { key: "--th-secondary",    label: t("admin.themes.vars.secondary") },
            { key: "--th-muted",        label: t("admin.themes.vars.muted") },
        ],
    },
    {
        key: "header",
        label: t("admin.themes.sections.header"),
        vars: [
            { key: "--th-header-bg",     label: t("admin.themes.vars.bg") },
            { key: "--th-header-border", label: t("admin.themes.vars.border") },
            { key: "--th-header-text",   label: t("admin.themes.vars.text") },
        ],
    },
    {
        key: "footer",
        label: t("admin.themes.sections.footer"),
        vars: [
            { key: "--th-footer-bg",     label: t("admin.themes.vars.bg") },
            { key: "--th-footer-border", label: t("admin.themes.vars.border") },
            { key: "--th-footer-text",   label: t("admin.themes.vars.text") },
        ],
    },
]);

const ALL_CSS_VARS = computed(() => CSS_SECTIONS.value.flatMap((s) => s.vars));

const DEFAULTS = {
    "--th-accent":         "#6366f1",
    "--th-accent-hover":   "#4f46e5",
    "--th-bg":             "#f9fafb",
    "--th-surface":        "#ffffff",
    "--th-surface-2":      "#f3f4f6",
    "--th-primary":        "#111827",
    "--th-secondary":      "#6b7280",
    "--th-muted":          "#9ca3af",
    "--th-header-bg":      "#ffffff",
    "--th-header-border":  "#e5e7eb",
    "--th-header-text":    "#111827",
    "--th-footer-bg":      "#ffffff",
    "--th-footer-border":  "#e5e7eb",
    "--th-footer-text":    "#9ca3af",
};

const DEFAULT_PRIMARY_COLOR = "#6366f1";

const editModal = reactive({ open: false, editing: null, saving: false, errors: {}, advanced: false });
const editForm = reactive({ name: "", description: "" });
const colorFields = reactive(Object.fromEntries(Object.keys(DEFAULTS).map((k) => [k, ""])));
const footerText = ref("");
const headerLogoMediaId = ref("");
const headerCustomText = ref("");
const headerMode = ref("default");
const primaryColor = ref(DEFAULT_PRIMARY_COLOR);

const configFromColors = computed(() => {
    const result = {};
    for (const key of Object.keys(DEFAULTS)) {
        if (colorFields[key] && colorFields[key] !== DEFAULTS[key]) {
            result[key] = colorFields[key];
        }
    }
    if (footerText.value.trim()) result["footer_text"] = footerText.value.trim();
    if (headerMode.value === "image" && headerLogoMediaId.value.trim()) {
        result["header_logo_media_id"] = headerLogoMediaId.value.trim();
    }
    if (headerMode.value === "text" && headerCustomText.value.trim()) {
        result["header_custom_text"] = headerCustomText.value.trim();
    }
    if (primaryColor.value && primaryColor.value.toLowerCase() !== DEFAULT_PRIMARY_COLOR) {
        result["primary_color"] = primaryColor.value;
    }
    return result;
});

function openEdit(theme) {
    editModal.editing = theme;
    editModal.errors = {};
    editModal.advanced = false;
    editForm.name = theme.name;
    editForm.description = theme.description ?? "";
    for (const { key } of ALL_CSS_VARS.value) {
        colorFields[key] = theme.config?.[key] ?? DEFAULTS[key];
    }
    footerText.value = theme.config?.["footer_text"] ?? "";
    headerLogoMediaId.value = theme.config?.["header_logo_media_id"] ?? "";
    headerCustomText.value = theme.config?.["header_custom_text"] ?? "";
    headerMode.value = theme.config?.["header_logo_media_id"] ? "image" : (theme.config?.["header_custom_text"] ? "text" : "default");
    primaryColor.value = theme.config?.["primary_color"] ?? DEFAULT_PRIMARY_COLOR;
    editModal.open = true;
}

function resetPrimaryColor() {
    primaryColor.value = DEFAULT_PRIMARY_COLOR;
}

async function submitEdit() {
    if (!editModal.editing) return;
    editModal.saving = true;
    editModal.errors = {};

    try {
        const url = props.updatePath.replace("__id__", editModal.editing.id);
        const response = await fetch(url, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                name: editForm.name,
                description: editForm.description,
                config: configFromColors.value,
            }),
        });
        const data = await response.json();
        if (!data.ok) {
            editModal.errors = data.errors ?? {};
            return;
        }
        const index = themeList.value.findIndex((item) => item.id === editModal.editing.id);
        if (index !== -1) {
            themeList.value[index] = data.theme;
        }
        editModal.open = false;
        toast.success(t("admin.themes.updated"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        editModal.saving = false;
    }
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deletingTheme = ref(null);

async function confirmDelete() {
    const theme = deletingTheme.value;
    if (!theme) return;
    try {
        const url = props.deletePath.replace("__id__", theme.id);
        const response = await fetch(url, { method: HttpMethod.Post });
        const data = await response.json();
        if (!data.ok) {
            toast.error(t(data.error ?? "common.error"));
            deletingTheme.value = null;
            return;
        }
        themeList.value = themeList.value.filter((item) => item.id !== theme.id);
        deletingTheme.value = null;
        toast.success(t("admin.themes.deleted"));
    } catch {
        toast.error(t("shared.common.error"));
    }
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-primary">{{ t("admin.themes.title") }}</h1>
            <AppButton variant="primary" size="md" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t("admin.themes.new") }}
            </AppButton>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
            <div
                v-for="theme in themeList"
                :key="theme.id"
                class="bg-surface border border-line rounded-xl p-5 flex flex-col gap-4"
                :class="theme.active ? 'border-accent-500/50 ring-1 ring-accent-500/30' : ''"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="flex flex-col gap-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-base font-semibold text-primary truncate">{{ theme.name }}</span>
                            <span class="text-xs font-mono bg-surface-2 text-muted px-1.5 py-0.5 rounded">{{ theme.slug }}</span>
                        </div>
                        <p v-if="theme.description" class="text-sm text-muted line-clamp-2">{{ theme.description }}</p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <AppBadge v-if="theme.active" color="emerald">
                            <Check class="w-3 h-3" :stroke-width="2.5" />
                            {{ t("admin.themes.active") }}
                        </AppBadge>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-lg border border-line shrink-0"
                        :style="{ backgroundColor: accentColor(theme) }"
                        :title="accentColor(theme)"
                    />
                    <div class="flex items-center gap-1 text-xs text-muted">
                        <Palette class="w-3.5 h-3.5" :stroke-width="2" />
                        <span>{{ t("admin.themes.templateCount", { count: theme.templateCount }) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-auto pt-2 border-t border-line">
                    <AppButton
                        size="sm"
                        :variant="theme.active ? 'ghost' : 'secondary'"
                        :disabled="theme.active"
                        class="flex-1"
                        v-on:click="activateTheme(theme)"
                    >
                        <Check class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("admin.themes.activate") }}
                    </AppButton>
                    <AppButton size="sm" variant="ghost" v-on:click="openEdit(theme)">
                        <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("admin.themes.edit") }}
                    </AppButton>
                    <AppButton
                        size="sm"
                        variant="ghost"
                        :disabled="theme.slug === 'default' || theme.active"
                        class="text-rose-400 hover:bg-rose-500/10 disabled:opacity-40"
                        v-on:click="deletingTheme = theme"
                    >
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                    </AppButton>
                </div>
            </div>
        </div>

        <AppModal :show="createModal.open" max-width="md" v-on:close="createModal.open = false">
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <h2 class="text-lg font-semibold text-primary">{{ t("admin.themes.new") }}</h2>
                <AppInput
                    v-model="createForm.name"
                    :label="t('shared.common.name')"
                    :error="createModal.errors.name ?? ''"
                    :required="true"
                />
                <AppInput
                    v-model="createForm.slug"
                    :label="t('admin.themes.slugLabel')"
                    :error="createModal.errors.slug ?? ''"
                    :required="true"
                />
                <AppTextarea
                    v-model="createForm.description"
                    :label="t('shared.common.description')"
                    :rows="2"
                />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="createModal.open = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="createModal.saving">{{ t("shared.common.create") }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="editModal.open" max-width="lg" :scrollable="true" v-on:close="editModal.open = false">
            <form class="space-y-5" v-on:submit.prevent="submitEdit">
                <h2 class="text-lg font-semibold text-primary">{{ t("admin.themes.edit") }}</h2>

                <AppInput
                    v-model="editForm.name"
                    :label="t('shared.common.name')"
                    :error="editModal.errors.name ?? ''"
                    :required="true"
                />
                <AppTextarea
                    v-model="editForm.description"
                    :label="t('shared.common.description')"
                    :rows="2"
                />

                <div class="space-y-1.5 pt-6 border-t border-line/60">
                    <span class="block text-xs text-secondary uppercase tracking-wide font-semibold">{{ t('admin.themes.primaryColor') }}</span>
                    <div class="flex items-center gap-3 bg-surface-2 rounded-lg px-3 py-2">
                        <input
                            type="color"
                            :value="primaryColor"
                            class="w-8 h-8 rounded cursor-pointer border border-line bg-transparent p-0.5"
                            v-on:input="primaryColor = $event.target.value"
                        >
                        <div class="flex flex-col min-w-0 flex-1">
                            <span class="text-xs font-medium text-primary">{{ t('admin.themes.primaryColorLabel') }}</span>
                            <span class="text-xs text-muted">{{ t('admin.themes.primaryColorHint') }}</span>
                        </div>
                        <span class="text-xs font-mono text-muted">{{ primaryColor }}</span>
                        <button type="button" class="text-xs text-muted hover:text-primary transition-colors" :title="t('admin.themes.resetColor')" v-on:click="resetPrimaryColor">↺</button>
                    </div>
                </div>

                <div v-for="section in CSS_SECTIONS" :key="section.key" class="space-y-1.5 pt-6 border-t border-line/60">
                    <span class="block text-xs text-secondary uppercase tracking-wide font-semibold">{{ section.label }}</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div v-for="cssVar in section.vars" :key="cssVar.key" class="flex items-center gap-3 bg-surface-2 rounded-lg px-3 py-2">
                            <input
                                type="color"
                                :value="colorFields[cssVar.key]"
                                class="w-8 h-8 rounded cursor-pointer border border-line bg-transparent p-0.5"
                                v-on:input="colorFields[cssVar.key] = $event.target.value"
                            >
                            <div class="flex flex-col min-w-0 flex-1">
                                <span class="text-xs font-medium text-primary">{{ cssVar.label }}</span>
                                <span class="text-xs font-mono text-muted truncate">{{ cssVar.key }}</span>
                            </div>
                            <button type="button" class="text-xs text-muted hover:text-primary transition-colors" :title="t('admin.themes.resetColor')" v-on:click="colorFields[cssVar.key] = DEFAULTS[cssVar.key]">↺</button>
                        </div>
                    </div>
                    <template v-if="section.key === 'header'">
                        <div class="space-y-2">
                            <span class="text-xs text-secondary uppercase tracking-wide">{{ t('admin.themes.headerContent') }}</span>
                            <div class="flex gap-2">
                                <button
                                    v-for="mode in [{k:'default',l:t('admin.themes.headerModeDefault')},{k:'text',l:t('admin.themes.headerModeText')},{k:'image',l:t('admin.themes.headerModeImage')}]"
                                    :key="mode.k"
                                    type="button"
                                    class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
                                    :class="headerMode === mode.k ? 'bg-accent-600 text-white border-accent-600' : 'bg-surface-2 text-secondary border-line hover:text-primary'"
                                    v-on:click="headerMode = mode.k"
                                >
                                    {{ mode.l }}
                                </button>
                            </div>
                            <AppInput v-if="headerMode === 'text'" v-model="headerCustomText" :label="t('admin.themes.headerCustomText')" :placeholder="t('admin.themes.headerTextPlaceholder')" />
                            <AppInput v-if="headerMode === 'image'" v-model="headerLogoMediaId" :label="t('admin.themes.headerLogoMediaId')" placeholder="42" />
                            <p v-if="headerMode === 'image'" class="text-xs text-muted">{{ t('admin.themes.headerMediaHint') }}</p>
                        </div>
                    </template>
                    <AppInput
                        v-if="section.key === 'footer'"
                        v-model="footerText"
                        :label="t('admin.themes.footerText')"
                        placeholder="© {year} {siteName}"
                    />
                </div>

                <AppModalFooter bordered>
                    <AppButton variant="ghost" size="md" v-on:click="editModal.open = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="editModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="!!deletingTheme" max-width="sm" v-on:close="deletingTheme = null">
            <h2 class="text-lg font-semibold text-primary">{{ t("admin.themes.deleteConfirm", { name: deletingTheme?.name ?? "" }) }}</h2>
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingTheme = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDelete">{{ t("shared.common.delete") }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
