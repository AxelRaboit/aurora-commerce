<script setup>
import { HttpMethod } from "@/shared/utils/httpMethod.js";
import { ref, reactive, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import AppButton from "@/shared/components/AppButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppToggle from "@/shared/components/AppToggle.vue";
import { Search, FileText } from "lucide-vue-next";

const props = defineProps({
    groups: { type: Object, default: () => ({}) },
    updatePath: { type: String, default: "" },
    mediaPickerPath: { type: String, default: "" },
    postSearchPath: { type: String, default: "" },
});

const { t } = useI18n();

const groupOrder = ["general", "reading", "localization", "branding", "seo", "system"];

const availableGroups = groupOrder.filter((groupName) => props.groups[groupName]);

const activeTab = ref(availableGroups[0] ?? "general");

const tabLabels = {
    general: () => t("admin.settings.tabs.general"),
    reading: () => t("admin.settings.tabs.reading"),
    localization: () => t("admin.settings.tabs.localization"),
    branding: () => t("admin.settings.tabs.branding"),
    seo: () => t("admin.settings.tabs.seo"),
    system: () => t("admin.settings.tabs.system"),
};

const fieldValues = reactive({});
for (const groupName of availableGroups) {
    for (const parameter of props.groups[groupName]) {
        fieldValues[parameter.key] = parameter.value ?? "";
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
            if (parameter.type === "post" && fieldValues[parameter.key]) {
                resolvePostLabel(parameter.key);
            }
        }
    }
});

async function saveGroup(groupName) {
    savingGroups[groupName] = true;
    const parameters = props.groups[groupName];

    try {
        for (const parameter of parameters) {
            const response = await fetch(props.updatePath, {
                method: HttpMethod.Post,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ key: parameter.key, value: fieldValues[parameter.key] }),
            });

            const result = await response.json();

            if (!result.ok) {
                toast.error(t("shared.common.error"));
                return;
            }
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
            <button
                v-for="groupName in availableGroups"
                :key="groupName"
                type="button"
                class="text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                :class="activeTab === groupName
                    ? 'bg-indigo-600/15 text-indigo-400'
                    : 'text-secondary hover:text-primary hover:bg-surface-2'"
                v-on:click="activeTab = groupName"
            >
                {{ tabLabels[groupName]?.() ?? groupName }}
            </button>
        </nav>

        <div class="flex md:hidden gap-1 flex-wrap mb-4 w-full">
            <button
                v-for="groupName in availableGroups"
                :key="groupName"
                type="button"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                :class="activeTab === groupName
                    ? 'bg-indigo-600/15 text-indigo-400'
                    : 'bg-surface-2 text-secondary hover:text-primary'"
                v-on:click="activeTab = groupName"
            >
                {{ tabLabels[groupName]?.() ?? groupName }}
            </button>
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
                        <template v-if="parameter.type === 'bool'">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-primary">{{ parameter.label }}</p>
                                    <p v-if="parameter.description" class="text-xs text-muted mt-0.5">{{ parameter.description }}</p>
                                </div>
                                <AppToggle
                                    :model-value="fieldValues[parameter.key] === '1'"
                                    v-on:update:model-value="fieldValues[parameter.key] = $event ? '1' : '0'"
                                />
                            </div>
                        </template>

                        <template v-else-if="parameter.type === 'post'">
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

                        <template v-else-if="parameter.type === 'int' || parameter.type === 'media'">
                            <AppInput
                                type="number"
                                :label="parameter.label"
                                :placeholder="parameter.key"
                                :model-value="fieldValues[parameter.key]"
                                v-on:update:model-value="fieldValues[parameter.key] = $event"
                            />
                            <p v-if="parameter.description" class="text-xs text-muted mt-1">{{ parameter.description }}</p>
                            <p v-if="parameter.type === 'media' && props.mediaPickerPath" class="text-xs text-muted mt-1">
                                <a :href="props.mediaPickerPath" target="_blank" rel="noopener" class="text-indigo-400 hover:underline">
                                    {{ t("admin.settings.browseMedia") }}
                                </a>
                            </p>
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
