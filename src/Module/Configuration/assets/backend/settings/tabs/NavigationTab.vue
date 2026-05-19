<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import { Save, X, RotateCcw, ChevronDown, ChevronRight } from "lucide-vue-next";
import { useNavAliases } from "../composables/useNavAliases.js";

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

        <div class="bg-surface border border-line rounded-xl p-4 sm:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-primary">{{ t('backend.settings.navAliases.title') }}</h3>
                    <p class="text-xs text-muted mt-1">{{ t('backend.settings.navAliases.help') }}</p>
                </div>
                <AppButton variant="ghost" size="sm" class="self-start sm:self-auto shrink-0" v-on:click="navAliases.resetAll">
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
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 px-3 py-2 bg-surface-2">
                        <div class="flex items-center gap-2 sm:w-48 sm:shrink-0">
                            <button
                                type="button"
                                class="flex items-center justify-center text-muted hover:text-primary transition-colors shrink-0 w-5 h-5"
                                :aria-expanded="navAliases.isSectionExpanded(section.id)"
                                v-on:click="navAliases.toggleSection(section.id)"
                            >
                                <component
                                    :is="navAliases.isSectionExpanded(section.id) ? ChevronDown : ChevronRight"
                                    class="w-3.5 h-3.5"
                                    :stroke-width="2"
                                />
                            </button>
                            <span class="text-xs text-secondary truncate flex-1" :title="navAliases.sectionDefaultLabel(section)">
                                {{ navAliases.sectionDefaultLabel(section) }}
                            </span>
                            <span class="text-xs text-muted sm:hidden shrink-0">{{ section.items?.length ?? 0 }}</span>
                        </div>
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <AppInput
                                v-model="navAliases.sectionAliases[section.id]"
                                :placeholder="navAliases.sectionDefaultLabel(section)"
                                class="flex-1"
                            />
                            <AppButton
                                variant="ghost"
                                size="sm"
                                :disabled="!navAliases.sectionAliases[section.id]"
                                :title="t('backend.settings.navAliases.resetItem')"
                                v-on:click="navAliases.resetSection(section.id)"
                            >
                                <X class="w-3.5 h-3.5" :stroke-width="2" />
                            </AppButton>
                        </div>
                        <span class="hidden sm:inline text-xs text-muted w-8 text-right shrink-0">{{ section.items?.length ?? 0 }}</span>
                    </div>
                    <div v-show="navAliases.isSectionExpanded(section.id)" class="p-3 space-y-2">
                        <div v-if="!section.items?.length" class="text-xs text-muted italic">
                            {{ t('backend.settings.navAliases.itemsEmpty') }}
                        </div>
                        <div
                            v-for="item in section.items"
                            :key="item.route"
                            class="flex flex-col sm:flex-row sm:items-center gap-2"
                        >
                            <span class="text-xs text-secondary truncate sm:w-40 sm:shrink-0" :title="navAliases.itemDefaultLabel(item)">
                                {{ navAliases.itemDefaultLabel(item) }}
                            </span>
                            <div class="flex items-center gap-2 flex-1 min-w-0">
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
            </div>

            <div class="pt-2 border-t border-line flex justify-end">
                <AppButton variant="primary" size="md" :loading="navAliases.saving.value" v-on:click="navAliases.saveAll">
                    <Save class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t('backend.settings.navAliases.save') }}
                </AppButton>
            </div>
        </div>
    </div>
</template>
