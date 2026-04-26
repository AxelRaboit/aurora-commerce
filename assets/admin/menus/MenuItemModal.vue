<script setup>
import { ref, reactive, computed, watch, nextTick } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Search, Check, X } from "lucide-vue-next";
import AppModal from "@/shared/components/AppModal.vue";
import AppButton from "@/shared/components/AppButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppMultiselect from "@/shared/components/AppMultiselect.vue";
import AppCheckbox from "@/shared/components/AppCheckbox.vue";

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, required: true },
    editing: { type: Object, default: null },
    targetTypes: { type: Array, default: () => [] },
    visibilities: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
    pickerPostsPath: { type: String, required: true },
    pickerTermsPath: { type: String, required: true },
    pickerPostTypesPath: { type: String, required: true },
    pickerTaxonomiesPath: { type: String, required: true },
});

const emit = defineEmits(["close", "save"]);

// ── Form state ───────────────────────────────────────────────────────────────

const form = reactive({
    targetType: "home",
    targetId: null,
    customUrl: "",
    openInNewTab: false,
    cssClass: "",
    visibility: "always",
    translations: {},
});

const targetLabel = ref(""); // Display the picked target's label (read-only)
const activeLocale = ref(props.locales[0] ?? "fr");
const saving = ref(false);

// ── Reset form when modal opens ──────────────────────────────────────────────

watch(() => props.show, async (open) => {
    if (!open) return;
    if (props.editing) {
        form.targetType = props.editing.targetType;
        form.targetId = props.editing.targetId ?? null;
        form.customUrl = props.editing.customUrl ?? "";
        form.openInNewTab = !!props.editing.openInNewTab;
        form.cssClass = props.editing.cssClass ?? "";
        form.visibility = props.editing.visibility ?? "always";
        form.translations = { ...(props.editing.translations ?? {}) };
        targetLabel.value = props.editing.targetPreview?.label ?? "";
    } else {
        form.targetType = "home";
        form.targetId = null;
        form.customUrl = "";
        form.openInNewTab = false;
        form.cssClass = "";
        form.visibility = "always";
        form.translations = {};
        targetLabel.value = "";
    }
    activeLocale.value = props.locales[0] ?? "fr";
    await nextTick();
});

// ── Target type behaviour ────────────────────────────────────────────────────

const requiresTargetId = computed(() =>
    ["post", "term", "post_type_archive"].includes(form.targetType),
);
const requiresCustomUrl = computed(() => form.targetType === "custom_url");
const requiresTranslationOverride = computed(() => form.targetType === "custom_url");

watch(() => form.targetType, (newType, oldType) => {
    if (newType === oldType) return;
    // Reset target-related fields when switching type
    form.targetId = null;
    targetLabel.value = "";
    if (newType !== "custom_url") form.customUrl = "";
});

// ── Autocomplete (post/term) ─────────────────────────────────────────────────

const pickerQuery = ref("");
const pickerResults = ref([]);
const pickerLoading = ref(false);
const pickerOpen = ref(false);

const postTypeFilter = ref(null);
const taxonomyFilter = ref(null);
const postTypeOptions = ref([]);
const taxonomyOptions = ref([]);

async function loadFilters() {
    try {
        if (form.targetType === "post") {
            const data = await jsonRequest(props.pickerPostTypesPath);
            if (data.ok) postTypeOptions.value = [{ id: 0, label: t("admin.menus.allTypes") }, ...data.items];
        }
        if (form.targetType === "term" && !taxonomyOptions.value.length) {
            const data = await jsonRequest(props.pickerTaxonomiesPath);
            if (data.ok) taxonomyOptions.value = [{ id: 0, label: t("admin.menus.allTaxonomies") }, ...data.items];
        }
        if (form.targetType === "post_type_archive") {
            const data = await jsonRequest(`${props.pickerPostTypesPath}?withArchive=1`);
            if (data.ok) postTypeOptions.value = data.items;
        }
    } catch {
        toast.error(t("shared.common.error"));
    }
}

watch(() => form.targetType, async () => {
    pickerQuery.value = "";
    pickerResults.value = [];
    await loadFilters();
});

watch(() => props.show, async (open) => {
    if (open) await loadFilters();
});

async function jsonRequest(url) {
    const response = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });
    return response.json();
}

const debouncedSearch = useDebounce(runSearch, 250);

async function runSearch() {
    pickerLoading.value = true;
    try {
        let url;
        if (form.targetType === "post") {
            url = `${props.pickerPostsPath}?q=${encodeURIComponent(pickerQuery.value)}`;
            if (postTypeFilter.value) url += `&postTypeId=${postTypeFilter.value}`;
        } else if (form.targetType === "term") {
            url = `${props.pickerTermsPath}?q=${encodeURIComponent(pickerQuery.value)}`;
            if (taxonomyFilter.value) url += `&taxonomyId=${taxonomyFilter.value}`;
        } else {
            return;
        }
        const data = await jsonRequest(url);
        if (data.ok) {
            pickerResults.value = data.items;
            pickerOpen.value = true;
        }
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        pickerLoading.value = false;
    }
}

// Re-fetch when filter changes (post type, taxonomy)
watch([postTypeFilter, taxonomyFilter], () => {
    if (form.targetType === "post" || form.targetType === "term") {
        runSearch();
    }
});

async function onPickerFocus() {
    if (pickerResults.value.length === 0) {
        await runSearch();
    } else {
        pickerOpen.value = true;
    }
}

function pickResult(result) {
    form.targetId = result.id;
    targetLabel.value = result.label;
    pickerOpen.value = false;
    pickerQuery.value = "";
    pickerResults.value = [];
}

function clearTarget() {
    form.targetId = null;
    targetLabel.value = "";
}

// ── Archive post type select ─────────────────────────────────────────────────

const archiveOptions = computed(() =>
    postTypeOptions.value
        .filter((pt) => pt.id !== 0)
        .map((pt) => ({ value: pt.id, label: pt.label })),
);

// ── Visibility select ────────────────────────────────────────────────────────

const visibilityOptions = computed(() =>
    props.visibilities.map((v) => ({ value: v.value, label: v.label })),
);

// ── Validation + save ────────────────────────────────────────────────────────

const errors = ref({});

function validate() {
    errors.value = {};
    if (requiresTargetId.value && !form.targetId) {
        errors.value.target = t("admin.menus.errors.target_required");
    }
    if (requiresCustomUrl.value && !form.customUrl.trim()) {
        errors.value.customUrl = t("admin.menus.errors.custom_url_required");
    }
    if (requiresTranslationOverride.value) {
        const hasAny = Object.values(form.translations).some((v) => v && v.trim());
        if (!hasAny) {
            errors.value.translations = t("admin.menus.errors.translation_required_for_custom_url");
        }
    }
    return Object.keys(errors.value).length === 0;
}

function save() {
    if (!validate()) return;
    emit("save", {
        targetType: form.targetType,
        targetId: requiresTargetId.value ? form.targetId : null,
        customUrl: requiresCustomUrl.value ? form.customUrl : null,
        openInNewTab: form.openInNewTab,
        cssClass: form.cssClass || null,
        visibility: form.visibility,
        translations: form.translations,
    });
}

function close() {
    emit("close");
}

// ── Translation helpers ──────────────────────────────────────────────────────

function setTranslation(locale, value) {
    form.translations = { ...form.translations, [locale]: value };
}

const targetTypeOptions = computed(() =>
    props.targetTypes.map((tt) => ({ value: tt.value, label: tt.label })),
);

</script>

<template>
    <AppModal :show="show" max-width="lg" v-on:close="close">
        <h3 class="text-lg font-bold text-primary mb-4">
            {{ editing ? t("admin.menus.editItem") : t("admin.menus.addItem") }}
        </h3>

        <form class="space-y-4" v-on:submit.prevent="save">
            <!-- Target type selector -->
            <AppMultiselect
                v-model="form.targetType"
                :options="targetTypeOptions"
                :label="t('admin.menus.targetType')"
                :allow-empty="false"
                :searchable="false"
            />

            <!-- Post picker -->
            <div v-if="form.targetType === 'post'" class="space-y-2">
                <label class="block text-xs font-semibold text-secondary uppercase tracking-wide">
                    {{ t("admin.menus.target") }}
                </label>
                <AppMultiselect
                    v-if="postTypeOptions.length"
                    v-model="postTypeFilter"
                    :options="postTypeOptions.map((pt) => ({ value: pt.id, label: pt.label }))"
                    :placeholder="t('admin.menus.allTypes')"
                    track-by="value"
                />
                <div v-if="form.targetId && targetLabel" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-surface-2 border border-line text-sm">
                    <Check class="w-4 h-4 text-emerald-400 shrink-0" :stroke-width="2" />
                    <span class="text-primary flex-1 truncate">{{ targetLabel }}</span>
                    <button type="button" class="text-muted hover:text-primary" v-on:click="clearTarget">
                        <X class="w-4 h-4" :stroke-width="2" />
                    </button>
                </div>
                <div v-else class="relative">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" :stroke-width="2" />
                        <input
                            v-model="pickerQuery"
                            type="text"
                            :placeholder="t('admin.menus.searchPostsPlaceholder')"
                            class="w-full pl-9 pr-3 py-2 rounded-md border border-line bg-surface text-sm text-primary focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            v-on:input="debouncedSearch"
                            v-on:focus="onPickerFocus"
                        >
                    </div>
                    <div v-if="pickerOpen && pickerResults.length" class="absolute left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-surface border border-line rounded-md shadow-lg z-10">
                        <button
                            v-for="result in pickerResults"
                            :key="result.id"
                            type="button"
                            class="w-full px-3 py-2 text-left text-sm hover:bg-surface-2 transition-colors"
                            v-on:click="pickResult(result)"
                        >
                            <p class="text-primary truncate">{{ result.label }}</p>
                            <p v-if="result.hint" class="text-xs text-muted truncate">{{ result.hint }}</p>
                        </button>
                    </div>
                </div>
                <p v-if="errors.target" class="text-xs text-rose-400">{{ errors.target }}</p>
            </div>

            <!-- Term picker -->
            <div v-else-if="form.targetType === 'term'" class="space-y-2">
                <label class="block text-xs font-semibold text-secondary uppercase tracking-wide">
                    {{ t("admin.menus.target") }}
                </label>
                <AppMultiselect
                    v-if="taxonomyOptions.length"
                    v-model="taxonomyFilter"
                    :options="taxonomyOptions.map((tx) => ({ value: tx.id, label: tx.label }))"
                    :placeholder="t('admin.menus.allTaxonomies')"
                    track-by="value"
                />
                <div v-if="form.targetId && targetLabel" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-surface-2 border border-line text-sm">
                    <Check class="w-4 h-4 text-emerald-400 shrink-0" :stroke-width="2" />
                    <span class="text-primary flex-1 truncate">{{ targetLabel }}</span>
                    <button type="button" class="text-muted hover:text-primary" v-on:click="clearTarget">
                        <X class="w-4 h-4" :stroke-width="2" />
                    </button>
                </div>
                <div v-else class="relative">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted" :stroke-width="2" />
                        <input
                            v-model="pickerQuery"
                            type="text"
                            :placeholder="t('admin.menus.searchTermsPlaceholder')"
                            class="w-full pl-9 pr-3 py-2 rounded-md border border-line bg-surface text-sm text-primary focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            v-on:input="debouncedSearch"
                            v-on:focus="onPickerFocus"
                        >
                    </div>
                    <div v-if="pickerOpen && pickerResults.length" class="absolute left-0 right-0 mt-1 max-h-64 overflow-y-auto bg-surface border border-line rounded-md shadow-lg z-10">
                        <button
                            v-for="result in pickerResults"
                            :key="result.id"
                            type="button"
                            class="w-full px-3 py-2 text-left text-sm hover:bg-surface-2 transition-colors"
                            v-on:click="pickResult(result)"
                        >
                            <p class="text-primary truncate">{{ result.label }}</p>
                            <p v-if="result.hint" class="text-xs text-muted truncate">{{ result.hint }}</p>
                        </button>
                    </div>
                </div>
                <p v-if="errors.target" class="text-xs text-rose-400">{{ errors.target }}</p>
            </div>

            <!-- Archive picker -->
            <div v-else-if="form.targetType === 'post_type_archive'">
                <AppMultiselect
                    v-model="form.targetId"
                    :options="archiveOptions"
                    :label="t('admin.menus.target')"
                    :error="errors.target"
                    :allow-empty="false"
                    track-by="value"
                />
            </div>

            <!-- Custom URL -->
            <AppInput
                v-else-if="form.targetType === 'custom_url'"
                v-model="form.customUrl"
                :label="t('admin.menus.customUrl')"
                placeholder="https://… or /path"
                :error="errors.customUrl"
                required
            />

            <!-- Translations -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-secondary uppercase tracking-wide">
                    {{ t("admin.menus.translations") }}
                </label>
                <p class="text-xs text-muted">{{ t("admin.menus.translationsHint") }}</p>
                <div class="flex gap-1 flex-wrap">
                    <button
                        v-for="locale in locales"
                        :key="locale"
                        type="button"
                        class="px-3 py-1 rounded-md text-xs font-medium transition-colors"
                        :class="activeLocale === locale ? 'bg-indigo-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                        v-on:click="activeLocale = locale"
                    >
                        {{ locale.toUpperCase() }}
                        <span v-if="form.translations[locale]" class="ml-1">●</span>
                    </button>
                </div>
                <input
                    :value="form.translations[activeLocale] ?? ''"
                    type="text"
                    :placeholder="t('admin.menus.translationPlaceholder')"
                    class="w-full px-3 py-2 rounded-md border border-line bg-surface text-sm text-primary placeholder:text-muted focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    v-on:input="setTranslation(activeLocale, $event.target.value)"
                >
                <p v-if="errors.translations" class="text-xs text-rose-400">{{ errors.translations }}</p>
            </div>

            <!-- Options -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-line">
                <AppMultiselect
                    v-model="form.visibility"
                    :options="visibilityOptions"
                    :label="t('admin.menus.visibility')"
                    :allow-empty="false"
                    :searchable="false"
                />
                <AppInput
                    v-model="form.cssClass"
                    :label="t('admin.menus.cssClass')"
                    placeholder="font-bold text-indigo-500"
                />
            </div>

            <AppCheckbox v-model="form.openInNewTab" :label="t('admin.menus.openInNewTab')" />

            <div class="flex justify-end gap-2 pt-3 border-t border-line">
                <AppButton variant="ghost" v-on:click="close">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton type="submit" variant="primary" :loading="saving">{{ t("shared.common.save") }}</AppButton>
            </div>
        </form>
    </AppModal>
</template>
