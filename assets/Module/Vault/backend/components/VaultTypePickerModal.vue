<script setup>
import { useI18n } from 'vue-i18n';
import AppModal from '@shared/components/overlay/AppModal.vue';
import { RECORD_TYPES, ICONS } from '@vault/backend/utils/recordTypes.js';
import { Vault as VaultIcon } from 'lucide-vue-next';

defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'select']);
const { t } = useI18n();
</script>

<template>
    <AppModal
        :show="show"
        :title="t('vault.entries.type')"
        :icon="VaultIcon"
        max-width="sm"
        v-on:close="emit('close')"
    >
        <div class="grid grid-cols-3 gap-2">
            <button
                v-for="recordType in RECORD_TYPES"
                :key="recordType.value"
                class="flex flex-col items-center gap-2 p-3 rounded-lg border border-line hover:border-accent-400 hover:bg-accent-50 dark:hover:bg-accent-900/20 transition-colors text-center group"
                v-on:click="emit('select', recordType.value); emit('close')"
            >
                <component
                    :is="ICONS[recordType.icon]"
                    class="w-5 h-5 text-secondary group-hover:text-accent-500 transition-colors"
                    :stroke-width="1.5"
                />
                <span class="text-xs text-secondary group-hover:text-primary transition-colors leading-tight">
                    {{ t('vault.types.' + recordType.value) }}
                </span>
            </button>
        </div>
    </AppModal>
</template>
