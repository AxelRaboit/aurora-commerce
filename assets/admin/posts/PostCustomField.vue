<script setup>
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { X, ImagePlus } from "lucide-vue-next";
import AppInput from "@/components/AppInput.vue";
import AppTextarea from "@/components/AppTextarea.vue";
import AppSelect from "@/components/AppSelect.vue";
import AppCheckbox from "@/components/AppCheckbox.vue";
import AppButton from "@/components/AppButton.vue";
import AppIconButton from "@/components/AppIconButton.vue";
import { statusBadge } from "@/utils/statusStyles.js";

const { t } = useI18n();

const props = defineProps({
    field: { type: Object, required: true },
    modelValue: { type: [String, Number, Boolean, Array, Object], default: null },
});

const emit = defineEmits(["update:modelValue"]);

const label = computed(() => props.field.label);

function update(value) {
    emit("update:modelValue", value);
}

// ── Reference picker ─────────────────────────────────────────────────────────
const isReference = computed(() => props.field.type === "reference");
const isMultiple = computed(() => props.field.options?.multiple === true);

const referenceIds = computed(() => {
    if (!isReference.value) return [];
    if (isMultiple.value) return Array.isArray(props.modelValue) ? props.modelValue.map(Number) : [];
    return props.modelValue ? [Number(props.modelValue)] : [];
});

const resolved = ref([]);
const search = ref("");
const results = ref([]);
const loading = ref(false);
const open = ref(false);
let timer = null;

watch(search, () => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(runSearch, 200);
});

async function runSearch() {
    loading.value = true;
    try {
        const url = new URL("/admin/posts/search", window.location.origin);
        if (search.value) url.searchParams.set("q", search.value);
        if (props.field.options?.postTypeId) url.searchParams.set("postTypeId", String(props.field.options.postTypeId));
        const response = await fetch(url);
        if (!response.ok) throw new Error();
        const data = await response.json();
        const exclude = new Set(referenceIds.value);
        results.value = (data.results ?? []).filter((r) => !exclude.has(r.id));
    } catch {
        results.value = [];
    } finally {
        loading.value = false;
    }
}

async function resolveMissingIds() {
    if (!isReference.value) return;
    const alreadyResolved = new Set(resolved.value.map((r) => r.id));
    const missing = referenceIds.value.filter((id) => !alreadyResolved.has(id));
    if (missing.length === 0) {
        resolved.value = resolved.value.filter((r) => referenceIds.value.includes(r.id));
        return;
    }
    try {
        const url = new URL("/admin/posts/search", window.location.origin);
        url.searchParams.set("ids", missing.join(","));
        const response = await fetch(url);
        if (!response.ok) return;
        const data = await response.json();
        for (const result of data.results ?? []) {
            if (!alreadyResolved.has(result.id)) resolved.value.push(result);
        }
    } catch {}
    resolved.value = resolved.value.filter((r) => referenceIds.value.includes(r.id));
}

watch(() => props.modelValue, resolveMissingIds, { immediate: true });

function addReference(result) {
    if (referenceIds.value.includes(result.id)) return;
    resolved.value.push(result);
    if (isMultiple.value) {
        update([...referenceIds.value, result.id]);
    } else {
        update(result.id);
        open.value = false;
    }
    search.value = "";
}

function removeReference(id) {
    resolved.value = resolved.value.filter((r) => r.id !== id);
    if (isMultiple.value) {
        update(referenceIds.value.filter((existing) => existing !== id));
    } else {
        update(null);
    }
}

// ── Media upload ─────────────────────────────────────────────────────────────
const uploading = ref(false);
const mediaInput = ref(null);

async function uploadMedia(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    uploading.value = true;
    try {
        const body = new FormData();
        body.append("image", file);
        const response = await fetch("/admin/media/upload", { method: "POST", body });
        if (!response.ok) throw new Error();
        const data = await response.json();
        if (data.success) update(data.file?.id ?? null);
    } catch {
        toast.error(t("common.error"));
    } finally {
        uploading.value = false;
        if (mediaInput.value) mediaInput.value.value = "";
    }
}
</script>

<template>
    <div>
        <!-- text -->
        <AppInput
            v-if="field.type === 'text'"
            :model-value="modelValue ?? ''"
            :label="label"
            :required="field.required"
            v-on:update:model-value="update"
        />

        <!-- textarea -->
        <AppTextarea
            v-else-if="field.type === 'textarea'"
            :model-value="modelValue ?? ''"
            :label="label"
            :required="field.required"
            :rows="4"
            v-on:update:model-value="update"
        />

        <!-- number -->
        <AppInput
            v-else-if="field.type === 'number'"
            type="number"
            :model-value="modelValue === null || modelValue === undefined ? '' : String(modelValue)"
            :label="label"
            :required="field.required"
            v-on:update:model-value="(v) => update(v === '' ? null : Number(v))"
        />

        <!-- date -->
        <AppInput
            v-else-if="field.type === 'date'"
            type="date"
            :model-value="modelValue ?? ''"
            :label="label"
            :required="field.required"
            v-on:update:model-value="update"
        />

        <!-- url -->
        <AppInput
            v-else-if="field.type === 'url'"
            type="url"
            :model-value="modelValue ?? ''"
            :label="label"
            :required="field.required"
            v-on:update:model-value="update"
        />

        <!-- email -->
        <AppInput
            v-else-if="field.type === 'email'"
            type="email"
            :model-value="modelValue ?? ''"
            :label="label"
            :required="field.required"
            v-on:update:model-value="update"
        />

        <!-- select -->
        <AppSelect
            v-else-if="field.type === 'select'"
            :model-value="modelValue ?? ''"
            :label="label"
            :required="field.required"
            v-on:update:model-value="update"
        >
            <option value="">—</option>
            <option v-for="choice in field.options?.choices ?? []" :key="choice.value" :value="choice.value">
                {{ choice.label }}
            </option>
        </AppSelect>

        <!-- checkbox -->
        <AppCheckbox
            v-else-if="field.type === 'checkbox'"
            :model-value="!!modelValue"
            :label="label"
            v-on:update:model-value="update"
        />

        <!-- media -->
        <div v-else-if="field.type === 'media'" class="flex flex-col gap-1.5">
            <label class="block text-xs text-secondary uppercase tracking-wide">{{ label }}</label>
            <div class="flex items-center gap-2">
                <div class="w-16 h-12 rounded-md border border-line bg-surface-2 overflow-hidden shrink-0 flex items-center justify-center">
                    <span v-if="modelValue" class="text-[10px] text-muted font-mono">#{{ modelValue }}</span>
                    <ImagePlus v-else class="w-4 h-4 text-muted" :stroke-width="2" />
                </div>
                <input
                    ref="mediaInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    v-on:change="uploadMedia"
                >
                <AppButton variant="secondary" size="sm" :loading="uploading" v-on:click="mediaInput?.click()">
                    {{ t("admin.posts.customField.upload") }}
                </AppButton>
                <AppButton v-if="modelValue" variant="ghost" size="sm" v-on:click="update(null)">
                    <X class="w-3.5 h-3.5" :stroke-width="2" />
                </AppButton>
            </div>
        </div>

        <!-- reference -->
        <div v-else-if="isReference" class="flex flex-col gap-1.5">
            <label class="block text-xs text-secondary uppercase tracking-wide">{{ label }}</label>

            <div v-if="resolved.length" class="flex flex-col gap-1">
                <div
                    v-for="result in resolved"
                    :key="result.id"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-surface-2 border border-line/60"
                >
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium" :class="statusBadge(result.status)">
                        {{ t("admin.stats.postStatus." + result.status) }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm text-primary truncate">{{ result.title ?? "(—)" }}</div>
                        <div class="text-xs text-muted truncate">{{ result.postType }}</div>
                    </div>
                    <AppIconButton color="rose" v-on:click="removeReference(result.id)">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                    </AppIconButton>
                </div>
            </div>

            <div v-if="isMultiple || resolved.length === 0" class="relative">
                <AppInput
                    v-model="search"
                    :placeholder="t('admin.posts.relatedPosts.searchPlaceholder')"
                    v-on:focus="open = true; runSearch()"
                    v-on:blur="setTimeout(() => { open = false; }, 150)"
                />
                <div
                    v-if="open && (results.length || loading)"
                    class="absolute z-10 mt-1 w-full max-h-64 overflow-y-auto rounded-md border border-line bg-surface shadow-lg"
                >
                    <div v-if="loading" class="px-3 py-2 text-xs text-muted">{{ t("common.loading") }}</div>
                    <button
                        v-for="result in results"
                        :key="result.id"
                        type="button"
                        class="w-full text-left px-3 py-2 hover:bg-surface-2 transition-colors flex items-center gap-2"
                        v-on:mousedown.prevent="addReference(result)"
                    >
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium" :class="statusBadge(result.status)">
                            {{ t("admin.stats.postStatus." + result.status) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-primary truncate">{{ result.title ?? "(—)" }}</div>
                            <div class="text-xs text-muted truncate">{{ result.postType }}</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
