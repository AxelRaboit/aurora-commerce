<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import { Save, X, RotateCcw, ChevronDown, ChevronRight } from "lucide-vue-next";
import { useNavAliases } from "@configuration/backend/settings/composables/useNavAliases.js";

const props = defineProps({
    groups: { type: Object, default: () => ({}) },
    updatePath: { type: String, default: "" },
    navSections: { type: Array, default: () => [] },
});

const { t } = useI18n();

const navAliases = useNavAliases({ groups: props.groups, updatePath: props.updatePath });
</script>

<template>
    <div class="space-y-6">
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
</template>
