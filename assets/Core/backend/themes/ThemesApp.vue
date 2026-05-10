<script setup>
import { useI18n } from "vue-i18n";
import { Palette, Check, Pencil, Trash2, Plus, Save, X } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppTextLinkButton from "@/shared/components/action/AppTextLinkButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import { useThemesList } from "@core/backend/themes/composables/useThemesList.js";
import { useThemesActivate } from "@core/backend/themes/composables/useThemesActivate.js";
import { useThemesCreate } from "@core/backend/themes/composables/useThemesCreate.js";
import { useThemesEdit } from "@core/backend/themes/composables/useThemesEdit.js";
import { useThemesDelete } from "@core/backend/themes/composables/useThemesDelete.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    themes: { type: Array, default: () => [] },
    activatePath: { type: String, default: "" },
    updatePath: { type: String, default: "" },
    createPath: { type: String, default: "" },
    deletePath: { type: String, default: "" },
    /**
     * Extra fields to register on the create + edit forms. Theme uses two
     * separate composables (create form is light, edit form is the CSS-config
     * panel) so the corresponding slots are also distinct:
     * extra-create-form-fields and extra-form-fields.
     */
    extraFields: { type: Object, default: () => ({}) },
});

const { themeList, accentColor } = useThemesList(props.themes);
const { activateTheme } = useThemesActivate(themeList, props.activatePath);
const { createModal, createForm, openCreate, submitCreate } = useThemesCreate(themeList, props.createPath, { extraFields: props.extraFields });
const { CSS_SECTIONS, DEFAULTS, editModal, editForm, colorFields, footerText, headerLogoMediaId, headerCustomText, headerMode, primaryColor, openEdit, resetPrimaryColor, submitEdit } = useThemesEdit(themeList, props.updatePath, { extraFields: props.extraFields });
const { deletingTheme, confirmDelete } = useThemesDelete(themeList, props.deletePath);
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-primary">{{ t("backend.themes.title") }}</h1>
            <AppButton v-if="can('core.themes.manage')" variant="primary" size="md" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t("backend.themes.new") }}
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
                            {{ t("backend.themes.active") }}
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
                        <span>{{ t("backend.themes.templateCount", { count: theme.templateCount }) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 mt-auto pt-2 border-t border-line">
                    <AppButton
                        v-if="can('core.themes.manage')"
                        size="sm"
                        :variant="theme.active ? 'ghost' : 'secondary'"
                        :disabled="theme.active"
                        class="flex-1"
                        v-on:click="activateTheme(theme)"
                    >
                        <Check class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("backend.themes.activate") }}
                    </AppButton>
                    <AppButton v-if="can('core.themes.manage')" size="sm" variant="ghost" v-on:click="openEdit(theme)">
                        <Pencil class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("backend.themes.edit") }}
                    </AppButton>
                    <AppButton
                        v-if="can('core.themes.manage')"
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

        <AppModal
            :show="createModal.open"
            max-width="md"
            :title="t('backend.themes.new')"
            :closeable="false"
            v-on:close="createModal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <AppInput
                    v-model="createForm.name"
                    :label="t('shared.common.name')"
                    :error="createModal.errors.name ?? ''"
                    :required="true"
                />
                <AppInput
                    v-model="createForm.slug"
                    :label="t('backend.themes.slugLabel')"
                    :error="createModal.errors.slug ?? ''"
                    :required="true"
                />
                <AppTextarea
                    v-model="createForm.description"
                    :label="t('shared.common.description')"
                    :rows="2"
                />
                <slot name="extra-create-form-fields" :form="createForm" :errors="createModal.errors" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="createModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="createModal.saving"><Plus class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.create") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="editModal.open"
            max-width="lg"
            :title="t('backend.themes.edit')"
            :closeable="false"
            v-on:close="editModal.open = false"
        >
            <form class="space-y-5" v-on:submit.prevent="submitEdit">
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
                <slot name="extra-form-fields" :form="editForm" :errors="editModal.errors" :theme="editModal.editing" />

                <div class="space-y-1.5 pt-6 border-t border-line/60">
                    <span class="block text-xs text-secondary uppercase tracking-wide font-semibold">{{ t('backend.themes.primaryColor') }}</span>
                    <div class="flex items-center gap-3 bg-surface-2 rounded-lg px-3 py-2">
                        <input
                            type="color"
                            :value="primaryColor"
                            class="w-8 h-8 rounded cursor-pointer border border-line bg-transparent p-0.5"
                            v-on:input="primaryColor = $event.target.value"
                        >
                        <div class="flex flex-col min-w-0 flex-1">
                            <span class="text-xs font-medium text-primary">{{ t('backend.themes.primaryColorLabel') }}</span>
                            <span class="text-xs text-muted">{{ t('backend.themes.primaryColorHint') }}</span>
                        </div>
                        <span class="text-xs font-mono text-muted">{{ primaryColor }}</span>
                        <AppTextLinkButton color="muted" size="xs" :title="t('backend.themes.resetColor')" v-on:click="resetPrimaryColor">↺</AppTextLinkButton>
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
                            <AppTextLinkButton color="muted" size="xs" :title="t('backend.themes.resetColor')" v-on:click="colorFields[cssVar.key] = DEFAULTS[cssVar.key]">↺</AppTextLinkButton>
                        </div>
                    </div>
                    <template v-if="section.key === 'header'">
                        <div class="space-y-2">
                            <span class="text-xs text-secondary uppercase tracking-wide">{{ t('backend.themes.headerContent') }}</span>
                            <div class="flex gap-2">
                                <button
                                    v-for="mode in [{k:'default',l:t('backend.themes.headerModeDefault')},{k:'text',l:t('backend.themes.headerModeText')},{k:'image',l:t('backend.themes.headerModeImage')}]"
                                    :key="mode.k"
                                    type="button"
                                    class="flex-1 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
                                    :class="headerMode === mode.k ? 'bg-accent-600 text-white border-accent-600' : 'bg-surface-2 text-secondary border-line hover:text-primary'"
                                    v-on:click="headerMode = mode.k"
                                >
                                    {{ mode.l }}
                                </button>
                            </div>
                            <AppInput v-if="headerMode === 'text'" v-model="headerCustomText" :label="t('backend.themes.headerCustomText')" :placeholder="t('backend.themes.headerTextPlaceholder')" />
                            <AppInput v-if="headerMode === 'image'" v-model="headerLogoMediaId" :label="t('backend.themes.headerLogoMediaId')" placeholder="42" />
                            <p v-if="headerMode === 'image'" class="text-xs text-muted">{{ t('backend.themes.headerMediaHint') }}</p>
                        </div>
                    </template>
                    <AppInput
                        v-if="section.key === 'footer'"
                        v-model="footerText"
                        :label="t('backend.themes.footerText')"
                        placeholder="© {year} {siteName}"
                    />
                </div>
            </form>
            <template #footer>
                <AppModalFooter bordered>
                    <AppButton variant="ghost" size="md" v-on:click="editModal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="editModal.saving"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.save") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingTheme"
            max-width="sm"
            :title="t('backend.themes.deleteConfirm', { name: deletingTheme?.name ?? '' })"
            :closeable="false"
            v-on:close="deletingTheme = null"
        >
            <div class="flex justify-end gap-2">
                <AppButton variant="ghost" size="md" v-on:click="deletingTheme = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
            </div>
        </AppModal>
    </div>
</template>
