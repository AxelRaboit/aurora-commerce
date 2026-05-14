<script setup>
import { reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppTooltip from "@/shared/components/overlay/AppTooltip.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppImagePickerField from "@/shared/components/form/AppImagePickerField.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppListItemButton from "@/shared/components/action/AppListItemButton.vue";
import AppTextLinkButton from "@/shared/components/action/AppTextLinkButton.vue";
import { Search, FileText, Lock, Save } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { ParameterType } from "@core/utils/enums/settings/parameterType.js";
import { useSettingsForm } from "@core/backend/settings/composables/useSettingsForm.js";
import { useSettingsPostPicker } from "@core/backend/settings/composables/useSettingsPostPicker.js";
import { useSettingsSequenceFilter } from "@core/backend/settings/composables/useSettingsSequenceFilter.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

const props = defineProps({
    groups: { type: Object, default: () => ({}) },
    updatePath: { type: String, default: "" },
    mediaPickerPath: { type: String, default: "" },
    postSearchPath: { type: String, default: "" },
    navSections: { type: Array, default: () => [] },
});

const { t } = useI18n();

const groupOrder = ["general", "reading", "localization", "branding", "seo", "system", "email", "sequences", "navigation"];
const availableGroups = groupOrder.filter((groupName) => props.groups[groupName] || groupName === "navigation");
const activeTab = ref(availableGroups[0] ?? "general");

const tabLabels = {
    general:    () => t("backend.settings.tabs.general"),
    reading:    () => t("backend.settings.tabs.reading"),
    localization: () => t("backend.settings.tabs.localization"),
    branding:   () => t("backend.settings.tabs.branding"),
    seo:        () => t("backend.settings.tabs.seo"),
    system:     () => t("backend.settings.tabs.system"),
    email:      () => t("backend.settings.tabs.email"),
    sequences:  () => t("backend.settings.tabs.sequences"),
    navigation: () => t("backend.settings.tabs.navigation"),
};

const tabDescriptions = {
    general:    () => t("backend.settings.tabs.general_description"),
    reading:    () => t("backend.settings.tabs.reading_description"),
    localization: () => t("backend.settings.tabs.localization_description"),
    branding:   () => t("backend.settings.tabs.branding_description"),
    seo:        () => t("backend.settings.tabs.seo_description"),
    system:     () => t("backend.settings.tabs.system_description"),
    email:      () => t("backend.settings.tabs.email_description"),
    sequences:  () => t("backend.settings.tabs.sequences_description"),
    navigation: () => t("backend.settings.tabs.navigation_description"),
};

const { fieldValues, mediaState, isLocked, lockReason, onBoolChange, onMediaChange, savingGroups, saveGroup } =
    useSettingsForm(props.groups, availableGroups, props.updatePath);

// Navigation aliases
const navAliasesRaw = props.groups?.navigation?.find?.((s) => s.key === "nav_section_aliases")?.value ?? "{}";
const navAliases = reactive((() => { try { return JSON.parse(navAliasesRaw) || {}; } catch { return {}; } })());
const { loading: aliasesSaving, request: aliasRequest } = useRequest();

async function saveNavAliases() {
    const data = await aliasRequest(props.updatePath, {
        key: "nav_section_aliases",
        value: JSON.stringify(navAliases),
    });
    if (data?.success) {
        toast.success(t("backend.settings.saved"));
    }
}

const { postPickerLabels, postPickerSearch, postPickerResults, postPickerOpen, resolvePostLabel, searchPosts, selectPost, clearPost, onPostPickerBlur, onPostPickerFocus } =
    useSettingsPostPicker(props.groups, availableGroups, fieldValues, props.postSearchPath);

const { sequenceSearch, paginatedSequences, sequencePage, sequenceTotalPages, goToSequencePage } =
    useSettingsSequenceFilter(props.groups);
</script>

<template>
    <div class="flex flex-col md:flex-row gap-6">
        <nav class="hidden md:flex flex-col w-44 shrink-0 gap-0.5">
            <AppTooltip
                v-for="groupName in availableGroups"
                :key="groupName"
                :title="tabLabels[groupName]?.() ?? groupName"
                :description="tabDescriptions[groupName]?.()"
                placement="right"
            >
                <AppTab
                    :active="activeTab === groupName"
                    v-on:click="activeTab = groupName"
                >
                    {{ tabLabels[groupName]?.() ?? groupName }}
                </AppTab>
            </AppTooltip>
        </nav>

        <div class="flex md:hidden gap-1 flex-wrap mb-4 w-full">
            <AppTooltip
                v-for="groupName in availableGroups"
                :key="groupName"
                :title="tabLabels[groupName]?.() ?? groupName"
                :description="tabDescriptions[groupName]?.()"
                placement="bottom"
            >
                <AppTab
                    :active="activeTab === groupName"
                    size="sm"
                    v-on:click="activeTab = groupName"
                >
                    {{ tabLabels[groupName]?.() ?? groupName }}
                </AppTab>
            </AppTooltip>
        </div>

        <div class="flex-1 min-w-0">
            <!-- Navigation aliases tab — custom UI, not a generic setting renderer -->
            <div v-show="activeTab === 'navigation'" class="bg-surface border border-line rounded-xl p-6 space-y-4">
                <p class="text-sm text-secondary">{{ t('backend.settings.tabs.navigation_description') }}</p>
                <div v-if="!navSections.length" class="text-sm text-muted">{{ t('backend.settings.navAliasesEmpty') }}</div>
                <div v-else class="space-y-3">
                    <div v-for="section in navSections" :key="section.id" class="flex items-center gap-4">
                        <span class="text-sm text-secondary w-36 shrink-0">{{ t(`backend.nav.sections.${section.id}`) }}</span>
                        <AppInput
                            v-model="navAliases[section.id]"
                            :placeholder="t(`backend.nav.sections.${section.id}`)"
                            class="flex-1"
                        />
                    </div>
                </div>
                <div class="pt-2">
                    <AppButton variant="primary" size="md" :loading="aliasesSaving" v-on:click="saveNavAliases">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.save') }}
                    </AppButton>
                </div>
            </div>

            <div
                v-for="groupName in availableGroups.filter(g => g !== 'navigation')"
                v-show="activeTab === groupName"
                :key="groupName"
            >
                <div class="bg-surface border border-line rounded-xl p-6 space-y-6">
                    <AppSearchInput
                        v-if="groupName === 'sequences'"
                        v-model="sequenceSearch"
                        :placeholder="t('backend.settings.sequenceSearch')"
                    />

                    <div
                        v-for="parameter in (groupName === 'sequences' ? paginatedSequences : groups[groupName])"
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
                                <AppTextLinkButton color="danger" size="xs" class="shrink-0" v-on:click="clearPost(parameter.key)">
                                    {{ t("shared.common.remove") }}
                                </AppTextLinkButton>
                            </div>
                            <div v-else class="flex items-center gap-1.5 text-sm text-muted italic mb-2">
                                <FileText class="w-3.5 h-3.5 opacity-60" :stroke-width="1.5" />
                                {{ t("backend.settings.noPageSelected") }}
                            </div>
                            <div class="relative">
                                <AppInput
                                    type="text"
                                    :placeholder="t('backend.settings.searchPost')"
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
                                    <AppListItemButton
                                        v-for="post in postPickerResults[parameter.key]"
                                        :key="post.id"
                                        class="justify-between border-b border-line last:border-0"
                                        v-on:click="selectPost(parameter.key, post)"
                                    >
                                        <span class="font-medium text-primary truncate">{{ post.title ?? "—" }}</span>
                                        <span class="text-xs text-muted shrink-0">{{ post.postType }}</span>
                                    </AppListItemButton>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-muted shrink-0">{{ t("backend.settings.orId") }}</span>
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
                                :hint="parameter.description ? parameter.description + ' — ' + t('backend.settings.mediaSquareHint') : t('backend.settings.mediaSquareHint')"
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

                        <template v-else-if="parameter.type === ParameterType.Select">
                            <AppSelect
                                :label="parameter.label"
                                :options="parameter.options ?? []"
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

                    <AppPagination
                        v-if="groupName === 'sequences' && sequenceTotalPages > 1"
                        :page="sequencePage"
                        :total-pages="sequenceTotalPages"
                        v-on:change="goToSequencePage"
                    />

                    <div class="pt-2 border-t border-line flex justify-end">
                        <AppButton
                            type="button"
                            variant="primary"
                            size="md"
                            :loading="savingGroups[groupName]"
                            v-on:click="saveGroup(groupName)"
                        >
                            <Save class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t("backend.settings.save") }}
                        </AppButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
