<script setup>
import { useI18n } from 'vue-i18n';
import AppModal from '@shared/components/overlay/AppModal.vue';
import AppModalFooter from '@shared/components/overlay/AppModalFooter.vue';
import AppButton from '@shared/components/action/AppButton.vue';
import AppInput from '@shared/components/form/AppInput.vue';
import AppMessage from '@shared/components/feedback/AppMessage.vue';
import { Trash2 } from 'lucide-vue-next';

defineProps({
    show: { type: Boolean, required: true },
    masterPassword: { type: String, required: true },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
});

const emit = defineEmits(['close', 'update:masterPassword', 'destroy']);
const { t } = useI18n();
</script>

<template>
    <AppModal
        :show="show"
        :title="t('vault.destroy.title')"
        :icon="Trash2"
        :closeable="false"
        v-on:close="emit('close')"
    >
        <div class="space-y-4">
            <AppMessage variant="danger">{{ t('vault.destroy.warning') }}</AppMessage>

            <AppInput
                :model-value="masterPassword"
                type="password"
                :label="t('vault.destroy.password_label')"
                :placeholder="t('vault.unlock.masterPasswordPlaceholder')"
                :error="error"
                v-on:update:model-value="emit('update:masterPassword', $event)"
                v-on:keydown.enter="emit('destroy')"
            />
        </div>

        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" :disabled="loading" v-on:click="emit('close')">
                    {{ t('shared.common.cancel') }}
                </AppButton>
                <AppButton
                    variant="danger"
                    :loading="loading"
                    :disabled="!masterPassword"
                    v-on:click="emit('destroy')"
                >
                    <Trash2 class="w-4 h-4" />
                    {{ t('vault.destroy.submit') }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
