<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppTooltip from "@/shared/components/overlay/AppTooltip.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSelect from "@/shared/components/form/AppSelect.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppImagePickerField from "@/shared/components/form/AppImagePickerField.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppListItemButton from "@/shared/components/action/AppListItemButton.vue";
import AppTextLinkButton from "@/shared/components/action/AppTextLinkButton.vue";
import AppColorSwatch from "@/shared/components/form/AppColorSwatch.vue";
import AppColorField from "@/shared/components/form/AppColorField.vue";
import { Search, FileText, Lock, Save, Plus, X, RotateCcw, ChevronDown, ChevronRight } from "lucide-vue-next";
import { ParameterType } from "@core/utils/enums/settings/parameterType.js";
import { useSettingsForm } from "@core/backend/settings/composables/useSettingsForm.js";
import { useSettingsPostPicker } from "@core/backend/settings/composables/useSettingsPostPicker.js";
import { useSettingsSequenceFilter } from "@core/backend/settings/composables/useSettingsSequenceFilter.js";
import { useSettingsTabs } from "@core/backend/settings/composables/useSettingsTabs.js";
import { useNavAliases } from "@core/backend/settings/composables/useNavAliases.js";
import { useColorPickerPresets } from "@core/backend/settings/composables/useColorPickerPresets.js";

const props = defineProps({
    groups: { type: Object, default: () => ({}) },
    updatePath: { type: String, default: "" },
    mediaPickerPath: { type: String, default: "" },
    postSearchPath: { type: String, default: "" },
    navSections: { type: Array, default: () => [] },
});

const { t } = useI18n();

const { availableGroups, activeTab, selectTab, tabLabel, tabDescription } = useSettingsTabs(props.groups);

const { fieldValues, mediaState, isLocked, lockReason, onBoolChange, onMediaChange, savingGroups, saveGroup } =
    useSettingsForm(props.groups, availableGroups, props.updatePath);

const navAliases = useNavAliases({ groups: props.groups, updatePath: props.updatePath });

const colorPresets = useColorPickerPresets({ groups: props.groups, updatePath: props.updatePath });

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
                :title="tabLabel(groupName)"
                :description="tabDescription(groupName)"
                placement="right"
            >
                <AppTab
                    :active="activeTab === groupName"
                    v-on:click="selectTab(groupName)"
                >
                    {{ tabLabel(groupName) }}
                </AppTab>
            </AppTooltip>
        </nav>

        <div class="flex md:hidden gap-1 flex-wrap mb-4 w-full">
            <AppTooltip
                v-for="groupName in availableGroups"
                :key="groupName"
                :title="tabLabel(groupName)"
                :description="tabDescription(groupName)"
                placement="bottom"
            >
                <AppTab
                    :active="activeTab === groupName"
                    size="sm"
                    v-on:click="selectTab(groupName)"
                >
                    {{ tabLabel(groupName) }}
                </AppTab>
            </AppTooltip>
        </div>

        <div class="flex-1 min-w-0">
            <!-- Navigation aliases tab — custom UI, not a generic setting renderer -->
            <div v-show="activeTab === 'navigation'" class="space-y-6">
                <p class="text-sm text-secondary">{{ t('backend.settings.tabs.navigation_description') }}</p>

                <!-- Sections aliases -->
                <div class="bg-surface border border-line rounded-xl p-6 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-primary">{{ t('backend.settings.navAliases.sectionsTitle') }}</h3>
                        <p class="text-xs text-muted mt-1">{{ t('backend.settings.navAliases.sectionsHelp') }}</p>
                    </div>
                    <div v-if="!navSections.length" class="text-sm text-muted">{{ t('backend.settings.navAliasesEmpty') }}</div>
                    <div v-else class="space-y-3">
                        <div v-for="section in navSections" :key="section.id" class="flex items-center gap-4">
                            <span class="text-sm text-secondary w-36 shrink-0">{{ t(`backend.nav.sections.${section.id}`) }}</span>
                            <AppInput
                                v-model="navAliases.sectionAliases[section.id]"
                                :placeholder="t(`backend.nav.sections.${section.id}`)"
                                class="flex-1"
                            />
                        </div>
                    </div>
                    <div class="pt-2 border-t border-line flex justify-end">
                        <AppButton variant="primary" size="md" :loading="navAliases.sectionsSaving.value" v-on:click="navAliases.saveSections">
                            <Save class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t('backend.settings.navAliases.saveSections') }}
                        </AppButton>
                    </div>
                </div>

                <!-- Items aliases -->
                <div class="bg-surface border border-line rounded-xl p-6 space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-primary">{{ t('backend.settings.navAliases.itemsTitle') }}</h3>
                            <p class="text-xs text-muted mt-1">{{ t('backend.settings.navAliases.itemsHelp') }}</p>
                        </div>
                        <AppButton variant="ghost" size="sm" v-on:click="navAliases.resetAllItems">
                            <RotateCcw class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t('backend.settings.navAliases.resetAll') }}
                        </AppButton>
                    </div>

                    <div v-if="!navSections.length" class="text-sm text-muted">{{ t('backend.settings.navAliasesEmpty') }}</div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="section in navSections"
                            :key="section.id"
                            class="border border-line rounded-lg overflow-hidden"
                        >
                            <button
                                type="button"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2 bg-surface-2 hover:bg-surface-3 transition-colors text-left"
                                v-on:click="navAliases.toggleSection(section.id)"
                            >
                                <span class="flex items-center gap-2 text-sm font-medium text-primary">
                                    <component
                                        :is="navAliases.isSectionExpanded(section.id) ? ChevronDown : ChevronRight"
                                        class="w-3.5 h-3.5 text-muted"
                                        :stroke-width="2"
                                    />
                                    {{ navAliases.sectionLabel(section) }}
                                </span>
                                <span class="text-xs text-muted">{{ section.items?.length ?? 0 }}</span>
                            </button>
                            <div v-show="navAliases.isSectionExpanded(section.id)" class="p-3 space-y-2">
                                <div v-if="!section.items?.length" class="text-xs text-muted italic">
                                    {{ t('backend.settings.navAliases.itemsEmpty') }}
                                </div>
                                <div
                                    v-for="item in section.items"
                                    :key="item.route"
                                    class="flex items-center gap-2"
                                >
                                    <span class="text-xs text-secondary w-40 shrink-0 truncate" :title="navAliases.itemDefaultLabel(item)">
                                        {{ navAliases.itemDefaultLabel(item) }}
                                    </span>
                                    <AppInput
                                        v-model="navAliases.itemAliases[item.route]"
                                        :placeholder="navAliases.itemDefaultLabel(item)"
                                        class="flex-1"
                                    />
                                    <AppButton
                                        variant="ghost"
                                        size="sm"
                                        :disabled="!navAliases.itemAliases[item.route]"
                                        :title="t('backend.settings.navAliases.resetItem')"
                                        v-on:click="navAliases.resetItem(item.route)"
                                    >
                                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                                    </AppButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-line flex justify-end">
                        <AppButton variant="primary" size="md" :loading="navAliases.itemsSaving.value" v-on:click="navAliases.saveItems">
                            <Save class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t('backend.settings.navAliases.saveItems') }}
                        </AppButton>
                    </div>
                </div>
            </div>

            <!-- Appearance tab — custom UI (palette editor) -->
            <div v-show="activeTab === 'appearance'" class="bg-surface border border-line rounded-xl p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-semibold text-primary">{{ t('backend.settings.appearance.color_presets.title') }}</h3>
                    <p class="text-xs text-muted mt-1">{{ t('backend.settings.appearance.color_presets.help') }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="color in colorPresets.presets.value"
                        :key="color"
                        class="relative group"
                    >
                        <AppColorSwatch :model-value="color" size="md" :disabled="true" />
                        <button
                            type="button"
                            class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-surface border border-line text-muted hover:text-danger hover:border-danger flex items-center justify-center opacity-0 group-hover:opacity-100 transition shadow-sm"
                            :title="t('backend.settings.appearance.color_presets.remove')"
                            v-on:click="colorPresets.remove(color)"
                        >
                            <X class="w-3 h-3" :stroke-width="2.5" />
                        </button>
                    </div>
                </div>

                <div>
                    <div v-if="colorPresets.showAddForm.value" class="border border-line rounded-lg p-4 bg-surface-2 space-y-3">
                        <AppColorField
                            v-model="colorPresets.newColor.value"
                            :label="t('backend.settings.appearance.color_presets.add')"
                            :show-hex="true"
                            size="md"
                        />
                        <div class="flex gap-2 justify-end">
                            <AppTextLinkButton color="muted" size="sm" v-on:click="colorPresets.cancelAdd">
                                {{ t('shared.common.cancel') }}
                            </AppTextLinkButton>
                            <AppButton variant="primary" size="sm" v-on:click="colorPresets.add">
                                {{ t('backend.settings.appearance.color_presets.confirm_add') }}
                            </AppButton>
                        </div>
                    </div>
                    <div v-else class="flex flex-wrap gap-2">
                        <AppButton variant="secondary" size="sm" v-on:click="colorPresets.openAddForm">
                            <Plus class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t('backend.settings.appearance.color_presets.add') }}
                        </AppButton>
                        <AppButton variant="ghost" size="sm" v-on:click="colorPresets.reset">
                            <RotateCcw class="w-3.5 h-3.5" :stroke-width="2" />
                            {{ t('backend.settings.appearance.color_presets.reset') }}
                        </AppButton>
                    </div>
                </div>

                <div class="pt-2 border-t border-line flex justify-end">
                    <AppButton
                        variant="primary"
                        size="md"
                        :loading="colorPresets.saving.value"
                        :disabled="!colorPresets.canSave.value"
                        v-on:click="colorPresets.save"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('backend.settings.save') }}
                    </AppButton>
                </div>
            </div>

            <div
                v-for="groupName in availableGroups.filter(g => g !== 'navigation' && g !== 'appearance')"
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
                            <AppMultiselect
                                v-if="(parameter.options ?? []).length > 10"
                                :label="parameter.label"
                                :options="parameter.options ?? []"
                                :model-value="fieldValues[parameter.key]"
                                v-on:update:model-value="fieldValues[parameter.key] = $event"
                            />
                            <AppSelect
                                v-else
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
