<script setup>
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref, reactive, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppImagePickerField from "@/shared/components/form/AppImagePickerField.vue";
import { Search, FileText, Lock } from "lucide-vue-next";
import { SettingErrorCode } from "@core/utils/enums/settings/settingErrorCode.js";
import { ParameterType } from "@core/utils/enums/settings/parameterType.js";

const props = defineProps({
    groups: { type: Object, default: () => ({}) },
    updatePath: { type: String, default: "" },
    mediaPickerPath: { type: String, default: "" },
    postSearchPath: { type: String, default: "" },
});

const { t } = useI18n();

const groupOrder = ["general", "reading", "localization", "branding", "seo", "system", "email"];

const availableGroups = groupOrder.filter((groupName) => props.groups[groupName]);

const activeTab = ref(availableGroups[0] ?? "general");

const tabLabels = {
    general: () => t("admin.settings.tabs.general"),
    reading: () => t("admin.settings.tabs.reading"),
    localization: () => t("admin.settings.tabs.localization"),
    branding: () => t("admin.settings.tabs.branding"),
    seo: () => t("admin.settings.tabs.seo"),
    system: () => t("admin.settings.tabs.system"),
    email: () => t("admin.settings.tabs.email"),
};

const fieldValues = reactive({});
const initialValues = {};
const parameterByKey = {};
const mediaState = reactive({}); // key -> { id, url } for type='media' parameters
for (const groupName of availableGroups) {
    for (const parameter of props.groups[groupName]) {
        const value = parameter.value ?? "";
        fieldValues[parameter.key] = value;
        initialValues[parameter.key] = value;
        parameterByKey[parameter.key] = parameter;
        if (parameter.type === ParameterType.Media) {
            mediaState[parameter.key] = {
                id: value ? Number(value) : null,
                url: parameter.mediaUrl ?? null,
            };
        }
    }
}

function onMediaChange(parameter, picked) {
    const id = picked?.id ?? null;
    const url = picked?.url ?? null;
    mediaState[parameter.key] = { id, url };
    fieldValues[parameter.key] = id ? String(id) : "";
}

function dependencyDepth(parameter) {
    let depth = 0;
    let current = parameter.requires;
    while (current) {
        depth++;
        current = parameterByKey[current]?.requires;
    }
    return depth;
}

function isLocked(parameter) {
    return parameter.requires ? fieldValues[parameter.requires] !== "1" : false;
}

function lockReason(parameter) {
    const parent = parameter.requires ? parameterByKey[parameter.requires] : null;
    return parent ? t("admin.settings.cascadeLocked", { parent: parent.label }) : "";
}

function onBoolChange(parameter, enabled) {
    fieldValues[parameter.key] = enabled ? "1" : "0";
    // Mirror server-side cascade: turning a parent off forces dependents off.
    if (!enabled) {
        for (const child of Object.values(parameterByKey)) {
            if (child.requires === parameter.key && fieldValues[child.key] === "1") {
                onBoolChange(child, false);
            }
        }
    }
}

const savingGroups = reactive({});

// Post picker state (for parameters of type "post")
const postPickerLabels = reactive({});   // key => { id, title }
const postPickerSearch = reactive({});   // key => string
const postPickerResults = reactive({});  // key => array
const postPickerOpen = reactive({});     // key => bool
const postPickerSearchAbort = {};        // key => AbortController

async function resolvePostLabel(key) {
    const id = fieldValues[key];
    if (!id || !props.postSearchPath) return;
    try {
        const response = await fetch(`${props.postSearchPath}?ids=${id}`);
        const json = await response.json();
        const post = json.results?.[0];
        if (post) postPickerLabels[key] = { id: post.id, title: post.title ?? `#${post.id}` };
    } catch { /* ignore */ }
}

async function searchPosts(key, query) {
    if (!props.postSearchPath) return;
    if (postPickerSearchAbort[key]) postPickerSearchAbort[key].abort();
    if (!query.trim()) { postPickerResults[key] = []; postPickerOpen[key] = false; return; }
    postPickerSearchAbort[key] = new AbortController();
    try {
        const response = await fetch(`${props.postSearchPath}?q=${encodeURIComponent(query)}`, {
            signal: postPickerSearchAbort[key].signal,
        });
        const json = await response.json();
        postPickerResults[key] = json.results ?? [];
        postPickerOpen[key] = true;
    } catch (error) {
        if (error.name !== "AbortError") postPickerOpen[key] = false;
    }
}

function selectPost(key, post) {
    fieldValues[key] = String(post.id);
    postPickerLabels[key] = { id: post.id, title: post.title ?? `#${post.id}` };
    postPickerSearch[key] = "";
    postPickerResults[key] = [];
    postPickerOpen[key] = false;
}

function clearPost(key) {
    fieldValues[key] = "";
    postPickerLabels[key] = null;
}

function onPostPickerBlur(key) {
    setTimeout(() => { postPickerOpen[key] = false; }, 150);
}

function onPostPickerFocus(key) {
    if (postPickerResults[key]?.length) postPickerOpen[key] = true;
}

onMounted(() => {
    for (const groupName of availableGroups) {
        for (const parameter of props.groups[groupName]) {
            if (parameter.type === ParameterType.Post && fieldValues[parameter.key]) {
                resolvePostLabel(parameter.key);
            }
        }
    }
});

async function saveGroup(groupName) {
    savingGroups[groupName] = true;

    // Only persist parameters that actually changed, ordered by dependency depth
    // ascending so parents are written before their children (avoids 409 when
    // enabling a chain like CRM → ERP → E-Commerce in a single save).
    const changed = props.groups[groupName]
        .filter((p) => fieldValues[p.key] !== initialValues[p.key])
        .sort((a, b) => dependencyDepth(a) - dependencyDepth(b));

    if (changed.length === 0) {
        savingGroups[groupName] = false;
        toast.success(t("admin.settings.saved"));
        return;
    }

    try {
        for (const parameter of changed) {
            const response = await fetch(props.updatePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ key: parameter.key, value: fieldValues[parameter.key] }),
            });

            const result = await response.json();

            if (!result.ok) {
                if (result.error === SettingErrorCode.CascadeViolation) {
                    const parent = parameterByKey[result.parentKey];
                    toast.error(t("admin.settings.cascadeLocked", { parent: parent?.label ?? result.parentKey }));
                } else {
                    toast.error(t("shared.common.error"));
                }
                return;
            }

            initialValues[parameter.key] = fieldValues[parameter.key];
        }

        toast.success(t("admin.settings.saved"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        savingGroups[groupName] = false;
    }
}
</script>

<template>
    <div class="flex flex-col md:flex-row gap-6">
        <nav class="hidden md:flex flex-col w-44 shrink-0 gap-0.5">
            <AppTab
                v-for="groupName in availableGroups"
                :key="groupName"
                :active="activeTab === groupName"
                v-on:click="activeTab = groupName"
            >
                {{ tabLabels[groupName]?.() ?? groupName }}
            </AppTab>
        </nav>

        <div class="flex md:hidden gap-1 flex-wrap mb-4 w-full">
            <AppTab
                v-for="groupName in availableGroups"
                :key="groupName"
                :active="activeTab === groupName"
                size="sm"
                v-on:click="activeTab = groupName"
            >
                {{ tabLabels[groupName]?.() ?? groupName }}
            </AppTab>
        </div>

        <div class="flex-1 min-w-0">
            <div
                v-for="groupName in availableGroups"
                v-show="activeTab === groupName"
                :key="groupName"
            >
                <div class="bg-surface border border-line rounded-xl p-6 space-y-6">
                    <div
                        v-for="parameter in groups[groupName]"
                        :key="parameter.key"
                    >
                        <template v-if="parameter.type === ParameterType.Bool">
                            <div class="flex items-center justify-between gap-4" :class="{ 'opacity-60': isLocked(parameter) }">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-primary flex items-center gap-1.5">
                                        {{ parameter.label }}
                                        <Lock v-if="isLocked(parameter)" class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                                    </p>
                                    <p v-if="parameter.description" class="text-xs text-muted mt-0.5">{{ parameter.description }}</p>
                                    <p v-if="isLocked(parameter)" class="text-xs text-warning mt-0.5">{{ lockReason(parameter) }}</p>
                                </div>
                                <AppToggle
                                    :model-value="!isLocked(parameter) && fieldValues[parameter.key] === '1'"
                                    :disabled="isLocked(parameter)"
                                    v-on:update:model-value="onBoolChange(parameter, $event)"
                                />
                            </div>
                        </template>

                        <template v-else-if="parameter.type === ParameterType.Post">
                            <p class="text-sm font-medium text-primary mb-1">{{ parameter.label }}</p>
                            <p v-if="parameter.description" class="text-xs text-muted mb-2">{{ parameter.description }}</p>
                            <div v-if="postPickerLabels[parameter.key]" class="flex items-center gap-3 p-3 border border-line rounded-lg bg-surface-2 mb-2">
                                <FileText class="w-4 h-4 shrink-0 text-accent" :stroke-width="2" />
                                <span class="flex-1 text-sm font-medium text-primary truncate">{{ postPickerLabels[parameter.key].title }}</span>
                                <span class="text-xs text-muted shrink-0">#{{ postPickerLabels[parameter.key].id }}</span>
                                <button type="button" class="text-xs text-muted hover:text-danger transition-colors shrink-0" v-on:click="clearPost(parameter.key)">
                                    {{ t("shared.common.remove") }}
                                </button>
                            </div>
                            <div v-else class="text-sm text-muted italic mb-2">{{ t("admin.settings.noPageSelected") }}</div>
                            <div class="relative">
                                <AppInput
                                    type="text"
                                    :placeholder="t('admin.settings.searchPost')"
                                    :model-value="postPickerSearch[parameter.key] ?? ''"
                                    v-on:update:model-value="postPickerSearch[parameter.key] = $event; searchPosts(parameter.key, $event)"
                                    v-on:blur="onPostPickerBlur(parameter.key)"
                                    v-on:focus="onPostPickerFocus(parameter.key)"
                                >
                                    <template #prefix>
                                        <Search class="w-3.5 h-3.5" :stroke-width="2" />
                                    </template>
                                </AppInput>
                                <div v-if="postPickerOpen[parameter.key] && postPickerResults[parameter.key]?.length" class="absolute z-20 left-0 right-0 mt-1 border border-line rounded-lg bg-surface shadow-lg overflow-hidden">
                                    <button
                                        v-for="post in postPickerResults[parameter.key]"
                                        :key="post.id"
                                        type="button"
                                        class="w-full flex items-center justify-between gap-3 px-3 py-2 text-sm text-left hover:bg-surface-2 transition-colors border-b border-line last:border-0"
                                        v-on:click="selectPost(parameter.key, post)"
                                    >
                                        <span class="font-medium text-primary truncate">{{ post.title ?? "—" }}</span>
                                        <span class="text-xs text-muted shrink-0">{{ post.postType }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-muted shrink-0">{{ t("admin.settings.orId") }}</span>
                                <div class="w-28">
                                    <AppInput
                                        type="number"
                                        :placeholder="'ID'"
                                        :model-value="fieldValues[parameter.key]"
                                        v-on:update:model-value="fieldValues[parameter.key] = $event; resolvePostLabel(parameter.key)"
                                    />
                                </div>
                            </div>
                        </template>

                        <template v-else-if="parameter.type === ParameterType.Media">
                            <AppImagePickerField
                                :label="parameter.label"
                                :hint="parameter.description ? parameter.description + ' — ' + t('admin.settings.mediaSquareHint') : t('admin.settings.mediaSquareHint')"
                                :model-value="mediaState[parameter.key]"
                                :size="96"
                                v-on:update:model-value="onMediaChange(parameter, $event)"
                            />
                        </template>

                        <template v-else-if="parameter.type === ParameterType.Int">
                            <AppInput
                                type="number"
                                :label="parameter.label"
                                :placeholder="parameter.key"
                                :model-value="fieldValues[parameter.key]"
                                v-on:update:model-value="fieldValues[parameter.key] = $event"
                            />
                            <p v-if="parameter.description" class="text-xs text-muted mt-1">{{ parameter.description }}</p>
                        </template>

                        <template v-else>
                            <AppInput
                                type="text"
                                :label="parameter.label"
                                :placeholder="parameter.key"
                                :model-value="fieldValues[parameter.key]"
                                v-on:update:model-value="fieldValues[parameter.key] = $event"
                            />
                            <p v-if="parameter.description" class="text-xs text-muted mt-1">{{ parameter.description }}</p>
                        </template>
                    </div>

                    <div class="pt-2 border-t border-line flex justify-end">
                        <AppButton
                            type="button"
                            variant="primary"
                            size="md"
                            :loading="savingGroups[groupName]"
                            v-on:click="saveGroup(groupName)"
                        >
                            {{ t("admin.settings.save") }}
                        </AppButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
