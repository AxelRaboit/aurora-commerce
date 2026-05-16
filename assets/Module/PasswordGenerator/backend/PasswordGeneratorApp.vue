<script setup>
import { useI18n } from 'vue-i18n';
import { calculatePasswordStrength } from '@shared/utils/validation/passwordStrength.js';
import { usePasswordGenerator } from '@password-generator/backend/composables/usePasswordGenerator.js';
import AppButton from '@shared/components/action/AppButton.vue';
import AppRange from '@/shared/components/form/toggle/AppRange.vue';
import AppToggle from '@shared/components/form/toggle/AppToggle.vue';
import { Copy, Check, RefreshCw, KeyRound } from 'lucide-vue-next';
import { computed } from 'vue';

const { t } = useI18n();
const { length, options, password, copied, entropy, generate, copy } = usePasswordGenerator();

const strengthScore = computed(() => calculatePasswordStrength(password.value));
const strengthColors = ['', 'bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-emerald-500', 'bg-emerald-600'];
const strengthLabels = ['', 'vault.setup.strength.weak', 'vault.setup.strength.fair', 'vault.setup.strength.good', 'vault.setup.strength.strong', 'vault.setup.strength.very_strong'];
</script>

<template>
    <div class="w-full max-w-lg mx-auto space-y-6">
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-accent-100 dark:bg-accent-900/30">
                <KeyRound class="w-7 h-7 text-accent-500" :stroke-width="1.5" />
            </div>
            <h2 class="text-xl font-semibold text-primary">{{ t('password_generator.title') }}</h2>
            <p class="text-sm text-secondary">{{ t('password_generator.description') }}</p>
        </div>

        <div class="rounded-xl border border-line bg-surface p-4 space-y-3">
            <div class="flex items-center gap-2">
                <code class="flex-1 font-mono text-sm text-primary bg-surface-2 rounded-lg px-3 py-2.5 break-all select-all min-h-[2.5rem]">
                    {{ password || '—' }}
                </code>
                <AppButton
                    variant="ghost"
                    size="md"
                    :title="copied ? t('password_generator.copied') : t('password_generator.copy')"
                    :disabled="!password"
                    v-on:click="copy"
                >
                    <Check v-if="copied" class="w-4 h-4 text-emerald-500" :stroke-width="2" />
                    <Copy v-else class="w-4 h-4" :stroke-width="2" />
                </AppButton>
            </div>

            <div v-if="password" class="space-y-1.5">
                <div class="flex gap-1">
                    <div
                        v-for="i in 5"
                        :key="i"
                        class="h-1.5 flex-1 rounded-full transition-colors"
                        :class="i <= strengthScore ? strengthColors[strengthScore] : 'bg-line'"
                    />
                </div>
                <div class="flex items-center justify-between">
                    <span v-if="strengthScore" class="text-xs text-secondary">{{ t(strengthLabels[strengthScore]) }}</span>
                    <span class="text-xs text-muted ml-auto">{{ t('password_generator.entropy', { bits: entropy() }) }}</span>
                </div>
            </div>

            <p v-else class="text-xs text-muted">{{ t('password_generator.no_charset') }}</p>
        </div>

        <div class="rounded-xl border border-line bg-surface p-4 space-y-4">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-primary">{{ t('password_generator.length') }}</span>
                    <span class="text-sm font-mono text-accent-500 tabular-nums w-8 text-right">{{ length }}</span>
                </div>
                <AppRange v-model="length" :min="8" :max="64" :step="1" />
                <div class="flex justify-between text-xs text-muted">
                    <span>8</span>
                    <span>64</span>
                </div>
            </div>

            <div class="border-t border-line pt-4 space-y-3">
                <p class="text-xs font-semibold text-secondary uppercase tracking-wide">{{ t('password_generator.characters') }}</p>
                <div class="space-y-2">
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
        </div>

        <AppButton variant="primary" size="md" class="w-full" v-on:click="generate">
            <RefreshCw class="w-4 h-4" :stroke-width="2" />
            {{ t('password_generator.generate') }}
        </AppButton>
    </div>
</template>
