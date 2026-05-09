<script setup>
import { useI18n } from 'vue-i18n';
import { useVaultSetup } from '@vault/backend/composables/useVaultSetup.js';
import { usePasswordStrength } from '@vault/backend/composables/usePasswordStrength.js';
import { useVaultSessionDuration } from '@vault/backend/composables/useVaultSessionDuration.js';
import AppButton from '@shared/components/action/AppButton.vue';
import AppInput from '@shared/components/form/AppInput.vue';
import AppToggle from '@shared/components/form/AppToggle.vue';
import AppSelect from '@shared/components/form/AppSelect.vue';
import VaultPasswordStrengthBar from '@vault/backend/components/VaultPasswordStrengthBar.vue';
import { ShieldCheck, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    setupPath: { type: String, required: true },
});

const emit = defineEmits(['setup-complete']);

const { t } = useI18n();
const { masterPassword, confirmPassword, errors, loading, submit } = useVaultSetup(props.setupPath);
const { score: passwordScore, strengthLabel, strengthColor } = usePasswordStrength(masterPassword);
const { keepUnlocked, keepDuration, durationOptions, resolvedDuration } = useVaultSessionDuration();

async function handleSubmit() {
    const payload = await submit(keepUnlocked.value, resolvedDuration());
    if (payload) emit('setup-complete', payload);
}
</script>

<template>
    <div class="flex items-center justify-center min-h-96">
        <div class="w-full max-w-md space-y-6">
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-accent-100 dark:bg-accent-900/30">
                    <ShieldCheck class="w-7 h-7 text-accent-500" :stroke-width="1.5" />
                </div>
                <h2 class="text-xl font-semibold text-primary">{{ t('vault.setup.title') }}</h2>
                <p class="text-sm text-secondary">{{ t('vault.setup.description') }}</p>
            </div>

            <form class="space-y-4" v-on:submit.prevent="handleSubmit">
                <div class="space-y-1">
                    <AppInput
                        v-model="masterPassword"
                        type="password"
                        :label="t('vault.setup.masterPassword')"
                        :placeholder="t('vault.setup.masterPasswordPlaceholder')"
                        :error="errors.masterPassword"
                        required
                    />
                    <VaultPasswordStrengthBar
                        v-if="masterPassword && !errors.masterPassword"
                        :score="passwordScore"
                        :strength-label="strengthLabel"
                        :strength-color="strengthColor"
                    />
                </div>

                <AppInput
                    v-model="confirmPassword"
                    type="password"
                    :label="t('vault.setup.confirmPassword')"
                    :placeholder="t('vault.setup.confirmPasswordPlaceholder')"
                    :error="errors.confirmPassword"
                    required
                />

                <div class="space-y-3 rounded-lg border border-line p-3 bg-surface-2/50">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-secondary">{{ t('vault.session.keep_unlocked') }}</span>
                        <AppToggle v-model="keepUnlocked" />
                    </div>
                    <AppSelect
                        v-if="keepUnlocked"
                        v-model="keepDuration"
                        :label="t('vault.session.duration')"
                        :options="durationOptions"
                    />
                </div>

                <div class="flex items-start gap-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                    <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" :stroke-width="2" />
                    <p class="text-xs text-amber-700 dark:text-amber-400">{{ t('vault.setup.warning') }}</p>
                </div>

                <AppButton
                    variant="primary"
                    size="md"
                    class="w-full"
                    type="submit"
                    :loading="loading"
                >
                    <ShieldCheck class="w-4 h-4" :stroke-width="2" />
                    {{ t('vault.setup.submit') }}
                </AppButton>
            </form>
        </div>
    </div>
</template>
