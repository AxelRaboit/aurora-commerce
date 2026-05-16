<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePasswordGenerator } from '@password-generator/backend/composables/usePasswordGenerator.js';
import { calculatePasswordStrength } from '@shared/utils/validation/passwordStrength.js';
import AppModal from '@shared/components/overlay/AppModal.vue';
import AppModalFooter from '@shared/components/overlay/AppModalFooter.vue';
import AppButton from '@shared/components/action/AppButton.vue';
import AppToggle from '@shared/components/form/toggle/AppToggle.vue';
import { RefreshCw, Lock } from 'lucide-vue-next';

defineProps({ show: { type: Boolean, required: true } });
const emit = defineEmits(['close', 'use']);

const { t } = useI18n();
const { length, options, password, entropy, generate } = usePasswordGenerator();

const strengthScore = computed(() => calculatePasswordStrength(password.value));
const strengthColors = ['', 'bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-emerald-500', 'bg-emerald-600'];
const strengthLabels = ['', 'vault.setup.strength.weak', 'vault.setup.strength.fair', 'vault.setup.strength.good', 'vault.setup.strength.strong', 'vault.setup.strength.very_strong'];

function usePassword() {
    emit('use', password.value);
}
</script>

<template>
    <AppModal
        :show="show"
        :title="t('password_generator.title')"
        :icon="Lock"
        max-width="sm"
        v-on:close="emit('close')"
    >
        <div class="space-y-4">
            <div class="rounded-lg border border-line bg-surface-2 px-3 py-2.5 space-y-2">
                <code class="block font-mono text-sm text-primary break-all select-all">
                    {{ password || '—' }}
                </code>
                <div v-if="password" class="space-y-1">
                    <div class="flex gap-1">
                        <div
                            v-for="i in 5"
                            :key="i"
                            class="h-1 flex-1 rounded-full transition-colors"
                            :class="i <= strengthScore ? strengthColors[strengthScore] : 'bg-line'"
                        />
                    </div>
                    <div class="flex justify-between text-xs text-muted">
                        <span v-if="strengthScore">{{ t(strengthLabels[strengthScore]) }}</span>
                        <span class="ml-auto">{{ t('password_generator.entropy', { bits: entropy() }) }}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-secondary">{{ t('password_generator.length') }}</span>
                    <span class="text-sm font-mono text-accent-500 tabular-nums">{{ length }}</span>
                </div>
                <input
                    v-model.number="length"
                    type="range"
                    min="8"
                    max="64"
                    step="1"
                    class="w-full accent-accent-500 cursor-pointer"
                >
            </div>

            <div class="space-y-2">
                <p class="text-xs font-semibold text-secondary uppercase tracking-wide">{{ t('password_generator.characters') }}</p>
                <div
                    v-for="{ key, label } in [
                        { key: 'uppercase', label: 'password_generator.uppercase' },
                        { key: 'lowercase', label: 'password_generator.lowercase' },
                        { key: 'digits', label: 'password_generator.digits' },
                        { key: 'symbols', label: 'password_generator.symbols' },
                    ]"
                    :key="key"
                    class="flex items-center justify-between"
                >
                    <span class="text-sm text-secondary">{{ t(label) }}</span>
                    <AppToggle :model-value="options[key]" v-on:update:model-value="options[key] = $event" />
                </div>
            </div>
        </div>

        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="generate">
                    <RefreshCw class="w-3.5 h-3.5" :stroke-width="2" />
                    {{ t('password_generator.generate') }}
                </AppButton>
                <AppButton variant="primary" size="md" :disabled="!password" v-on:click="usePassword">
                    {{ t('vault.fields.usePassword') }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
