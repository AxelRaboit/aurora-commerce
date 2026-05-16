<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppModal from '@shared/components/overlay/AppModal.vue';
import AppModalFooter from '@shared/components/overlay/AppModalFooter.vue';
import AppButton from '@shared/components/action/AppButton.vue';
import AppIconButton from '@shared/components/action/AppIconButton.vue';
import AppInput from '@shared/components/form/input/AppInput.vue';
import AppTextarea from '@shared/components/form/input/AppTextarea.vue';
import AppSelect from '@shared/components/form/select/AppSelect.vue';
import { ICONS, getRecordType, PASSWORD_FIELDS, TEXTAREA_FIELDS } from '@vault/backend/utils/recordTypes.js';
import { useRevealedFields } from '@vault/backend/composables/useRevealedFields.js';
import { useVaultPasswordPicker } from '@vault/backend/composables/useVaultPasswordPicker.js';
import { useVaultFolderOptions } from '@vault/backend/composables/useVaultFolderOptions.js';
import VaultTypePickerModal from '@vault/backend/components/VaultTypePickerModal.vue';
import VaultPasswordPickerModal from '@vault/backend/components/VaultPasswordPickerModal.vue';
import { Save, X, Star, ChevronDown, Eye, EyeOff, Wand2, Vault as VaultIcon, Pencil } from 'lucide-vue-next';

const form = defineModel({ type: Object, required: true });

const props = defineProps({
    show: { type: Boolean, default: false },
    mode: { type: String, default: 'create' },
    errors: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
    folders: { type: Array, default: () => [] },
    title: { type: String, default: '' },
});

const emit = defineEmits(['close', 'submit', 'type-change']);

const { t } = useI18n();
const showTypePicker = ref(false);
const passwordPicker = useVaultPasswordPicker(form);
const { revealedFields, toggleReveal } = useRevealedFields(() => props.show);
const { folderOptions } = useVaultFolderOptions(computed(() => props.folders));

const currentRecordType = computed(() => getRecordType(form.value.type));
</script>

<template>
    <AppModal
        :show="show"
        :title="title || t('vault.entries.' + mode)"
        :icon="mode === 'edit' ? Pencil : VaultIcon"
        :closeable="false"
        v-on:close="emit('close')"
    >
        <form class="space-y-4" v-on:submit.prevent="emit('submit')">
            <div class="flex items-center gap-3">
                <AppButton
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="border border-line hover:border-accent-400 bg-surface-2"
                    v-on:click="showTypePicker = true"
                >
                    <component :is="ICONS[currentRecordType.icon]" class="w-4 h-4" :stroke-width="1.5" />
                    {{ t('vault.types.' + form.type) }}
                    <ChevronDown class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                </AppButton>

                <AppButton
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="ml-auto"
                    :class="form.isFavorite ? 'text-amber-500 bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100' : ''"
                    v-on:click="form.isFavorite = !form.isFavorite"
                >
                    <Star class="w-3.5 h-3.5" :stroke-width="2" :fill="form.isFavorite ? 'currentColor' : 'none'" />
                    {{ t('vault.entries.favorite') }}
                </AppButton>
            </div>

            <AppInput
                v-model="form.title"
                :label="t('vault.entries.title')"
                :placeholder="t('vault.entries.titlePlaceholder')"
                :error="errors.title"
                required
            />

            <AppInput
                v-if="currentRecordType.fields.includes('url') || form.url"
                v-model="form.url"
                :label="t('vault.entries.url')"
                :placeholder="t('vault.entries.urlPlaceholder')"
            />

            <AppSelect
                v-model="form.folderId"
                :label="t('vault.entries.folder')"
                :options="folderOptions"
            />

            <div class="border-t border-line pt-4 space-y-3">
                <div v-for="field in currentRecordType.fields" :key="field" class="relative">
                    <template v-if="field === 'url'" />
                    <template v-else-if="TEXTAREA_FIELDS.includes(field)">
                        <AppTextarea
                            v-model="form.fields[field]"
                            :label="t('vault.fields.' + field)"
                            :placeholder="t('vault.fields.' + field + 'Placeholder', field)"
                            :rows="5"
                            class="font-mono text-xs"
                        />
                    </template>
                    <template v-else-if="PASSWORD_FIELDS.includes(field)">
                        <div class="relative">
                            <AppInput
                                v-model="form.fields[field]"
                                :type="revealedFields.has(field) ? 'text' : 'password'"
                                :label="t('vault.fields.' + field)"
                                :placeholder="t('vault.fields.' + field + 'Placeholder', field)"
                            />
                            <div class="absolute right-2 top-7 flex items-center gap-0.5">
                                <AppIconButton
                                    type="button"
                                    color="accent"
                                    :title="t('password_generator.title')"
                                    v-on:click="passwordPicker.open(field)"
                                >
                                    <Wand2 class="w-4 h-4" :stroke-width="1.5" />
                                </AppIconButton>
                                <AppIconButton
                                    type="button"
                                    v-on:click="toggleReveal(field)"
                                >
                                    <component :is="revealedFields.has(field) ? EyeOff : Eye" class="w-4 h-4" :stroke-width="1.5" />
                                </AppIconButton>
                            </div>
                        </div>
                    </template>
                    <template v-else-if="field === 'notes'">
                        <AppTextarea
                            v-model="form.fields.notes"
                            :label="t('vault.fields.notes')"
                            :placeholder="t('vault.fields.notesPlaceholder')"
                            rows="3"
                        />
                    </template>
                    <template v-else>
                        <AppInput
                            v-model="form.fields[field]"
                            :label="t('vault.fields.' + field)"
                            :placeholder="t('vault.fields.' + field + 'Placeholder', field)"
                        />
                    </template>
                </div>
            </div>
        </form>

        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" type="button" v-on:click="emit('close')">
                    <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}
                </AppButton>
                <AppButton variant="primary" size="md" :loading="loading" v-on:click="emit('submit')">
                    <Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>

    <VaultTypePickerModal :show="showTypePicker" v-on:close="showTypePicker = false" v-on:select="emit('type-change', $event)" />
    <VaultPasswordPickerModal :show="passwordPicker.show.value" v-on:close="passwordPicker.close()" v-on:use="passwordPicker.apply" />
</template>
