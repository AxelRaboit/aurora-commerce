<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppModal from '@shared/components/overlay/AppModal.vue';
import AppModalFooter from '@shared/components/overlay/AppModalFooter.vue';
import AppButton from '@shared/components/action/AppButton.vue';
import AppInput from '@shared/components/form/AppInput.vue';
import VaultPasswordStrengthBar from '@vault/backend/components/VaultPasswordStrengthBar.vue';
import { usePasswordStrength } from '@vault/backend/composables/usePasswordStrength.js';
import AppMessage from '@shared/components/feedback/AppMessage.vue';
import AppProgressBar from '@shared/components/feedback/AppProgressBar.vue';
import { ShieldCheck, X, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    step: { type: Number, default: 1 },
    currentPassword: { type: String, default: '' },
    newPassword: { type: String, default: '' },
    confirmPassword: { type: String, default: '' },
    progress: { type: Number, default: 0 },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits([
    'close',
    'update:currentPassword', 'update:newPassword', 'update:confirmPassword',
    'verify', 'change',
]);

const { t } = useI18n();
const newPasswordRef = computed(() => ({ value: props.newPassword }));
const { score, strengthLabel, strengthColor } = usePasswordStrength(newPasswordRef);
const title = computed(() => props.step === 3 ? t('vault.change_password.processing') : t('vault.change_password.title'));
</script>

<template>
    <AppModal
        :show="show"
        max-width="sm"
        :title="title"
        :icon="ShieldCheck"
        :closeable="false"
        v-on:close="emit('close')"
    >
        <div class="space-y-4">
            <template v-if="step === 1">
                <p class="text-sm text-secondary">{{ t('vault.change_password.step1_hint') }}</p>
                <AppInput
                    :model-value="currentPassword"
                    type="password"
                    :label="t('vault.unlock.masterPassword')"
                    :placeholder="t('vault.unlock.masterPasswordPlaceholder')"
                    :error="errors.currentPassword"
                    required
                    autofocus
                    v-on:update:model-value="emit('update:currentPassword', $event)"
                />
            </template>
            <template v-else-if="step === 2">
                <AppMessage variant="warning">{{ t('vault.change_password.warning') }}</AppMessage>
                <div class="space-y-1">
                    <AppInput
                        :model-value="newPassword"
                        type="password"
                        :label="t('vault.change_password.new_password')"
                        :placeholder="t('vault.setup.masterPasswordPlaceholder')"
                        :error="errors.newPassword"
                        required
                        v-on:update:model-value="emit('update:newPassword', $event)"
                    />
                    <VaultPasswordStrengthBar
                        v-if="newPassword && !errors.newPassword"
                        :score="score"
                        :strength-label="strengthLabel"
                        :strength-color="strengthColor"
                    />
                </div>
                <AppInput
                    :model-value="confirmPassword"
                    type="password"
                    :label="t('vault.setup.confirmPassword')"
                    :placeholder="t('vault.setup.confirmPasswordPlaceholder')"
                    :error="errors.confirmPassword"
                    required
                    v-on:update:model-value="emit('update:confirmPassword', $event)"
                />
            </template>
            <template v-else>
                <p class="text-sm text-secondary text-center">{{ t('vault.change_password.processing_hint') }}</p>
                <AppProgressBar :value="progress" :show-label="true" />
            </template>
        </div>

        <template #footer>
            <AppModalFooter v-if="step === 1">
                <AppButton variant="ghost" size="md" v-on:click="emit('close')">
                    <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}
                </AppButton>
                <AppButton variant="primary" size="md" v-on:click="emit('verify')">
                    {{ t('shared.common.next') }} <ArrowRight class="w-3.5 h-3.5" :stroke-width="2" />
                </AppButton>
            </AppModalFooter>
            <AppModalFooter v-else-if="step === 2">
                <AppButton variant="ghost" size="md" v-on:click="emit('close')">
                    <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}
                </AppButton>
                <AppButton variant="primary" size="md" v-on:click="emit('change')">
                    <ShieldCheck class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('vault.change_password.submit') }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
