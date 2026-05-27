<script setup>
import { useI18n } from 'vue-i18n';
import { useVaultUnlock } from '@tools/backend/vault/composables/useVaultUnlock.js';
import { useVaultSessionDuration } from '@tools/backend/vault/composables/useVaultSessionDuration.js';
import AppButton from '@shared/components/action/AppButton.vue';
import AppInput from '@shared/components/form/input/AppInput.vue';
import AppToggle from '@shared/components/form/toggle/AppToggle.vue';
import AppSelect from '@shared/components/form/select/AppSelect.vue';
import { Lock } from 'lucide-vue-next';

defineProps({
    saltBase64: { type: String, required: true },
    hasEntries: { type: Boolean, default: false },
});

const emit = defineEmits(['unlock']);

const { t } = useI18n();
const { masterPassword, errors, loading, buildPayload } = useVaultUnlock();
const { keepUnlocked, keepDuration, durationOptions, resolvedDuration } = useVaultSessionDuration();

function submit() {
    const payload = buildPayload(keepUnlocked.value, resolvedDuration());
    if (payload) emit('unlock', payload);
}
</script>

<template>
    <div class="flex items-center justify-center min-h-96">
        <div class="w-full max-w-sm space-y-6">
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-surface-2 border border-line">
                    <Lock class="w-7 h-7 text-secondary" :stroke-width="1.5" />
                </div>
                <h2 class="text-xl font-semibold text-primary">{{ t('vault.unlock.title') }}</h2>
            </div>

            <form class="space-y-4" v-on:submit.prevent="submit">
                <AppInput
                    v-model="masterPassword"
                    type="password"
                    :label="t('vault.unlock.master_password')"
                    :placeholder="t('vault.unlock.master_password_placeholder')"
                    :error="errors.masterPassword"
                    required
                    autofocus
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

                <AppButton
                    variant="primary"
                    size="md"
                    class="w-full"
                    type="submit"
                    :loading="loading"
                >
                    <Lock class="w-4 h-4" :stroke-width="2" />
                    {{ t('vault.unlock.submit') }}
                </AppButton>
            </form>
        </div>
    </div>
</template>
