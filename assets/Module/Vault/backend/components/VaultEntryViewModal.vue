<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import AppModal from '@shared/components/overlay/AppModal.vue';
import AppModalFooter from '@shared/components/overlay/AppModalFooter.vue';
import AppButton from '@shared/components/action/AppButton.vue';
import AppIconButton from '@shared/components/action/AppIconButton.vue';
import AppLink from '@shared/components/nav/AppLink.vue';
import { ICONS, getRecordType, PASSWORD_FIELDS, TEXTAREA_FIELDS } from '@vault/backend/utils/recordTypes.js';
import { useRevealedFields } from '@vault/backend/composables/useRevealedFields.js';
import { Eye, EyeOff, Copy, Star, ExternalLink, X } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    entry: { type: Object, default: null },
    decryptedFields: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close']);

const { t } = useI18n();
const { revealedFields, toggleReveal } = useRevealedFields(() => props.show);

const recordType = computed(() => props.entry ? getRecordType(props.entry.type) : null);
const TypeIcon = computed(() => ICONS[recordType.value?.icon] ?? ICONS['key-round']);

const visibleFields = computed(() => {
    if (!recordType.value) return [];
    return recordType.value.fields.filter((field) => {
        if (field === 'url') return false;
        const value = props.decryptedFields[field];
        return value !== undefined && value !== null && value !== '';
    });
});

async function copy(field, value) {
    try {
        await navigator.clipboard.writeText(value);
        toast.success(t('vault.view.copied', { field: t('vault.fields.' + field) }));

        if (PASSWORD_FIELDS.includes(field)) {
            setTimeout(async () => {
                try {
                    const current = await navigator.clipboard.readText();
                    if (current === value) {
                        await navigator.clipboard.writeText('');
                    }
                } catch {}
            }, 30_000);
        }
    } catch {
        toast.error(t('shared.common.error'));
    }
}

function close() {
    emit('close');
}
</script>

<template>
    <AppModal :show="show" max-width="sm" :closeable="false" v-on:close="close">
        <template v-if="entry">
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-surface-2 border border-line shrink-0">
                        <component :is="TypeIcon" class="w-5 h-5 text-secondary" :stroke-width="1.5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold text-primary truncate">{{ entry.title }}</h3>
                            <Star
                                v-if="entry.isFavorite"
                                class="w-3.5 h-3.5 text-amber-400 shrink-0"
                                :stroke-width="2"
                                fill="currentColor"
                            />
                        </div>
                        <p class="text-xs text-muted">{{ t('vault.types.' + entry.type) }}</p>
                        <AppLink
                            v-if="entry.url"
                            :href="entry.url"
                            target="_blank"
                            rel="noopener"
                            class="text-xs text-accent-400 hover:underline flex items-center gap-1 mt-0.5"
                        >
                            {{ entry.url }}
                            <ExternalLink class="w-3 h-3" :stroke-width="2" />
                        </AppLink>
                    </div>
                </div>

                <div v-if="entry.folderName" class="flex items-center gap-1.5 text-xs text-muted">
                    <span
                        v-if="entry.folderColor"
                        class="w-2 h-2 rounded-full"
                        :style="{ backgroundColor: entry.folderColor }"
                    />
                    {{ entry.folderName }}
                </div>

                <div class="divide-y divide-line/40">
                    <div
                        v-for="field in visibleFields"
                        :key="field"
                        class="flex items-start gap-3 py-3"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-muted mb-0.5">{{ t('vault.fields.' + field) }}</p>
                            <template v-if="field === 'notes'">
                                <p class="text-sm text-primary whitespace-pre-wrap break-words">{{ decryptedFields[field] }}</p>
                            </template>
                            <template v-else-if="TEXTAREA_FIELDS.includes(field)">
                                <p
                                    class="text-xs text-primary font-mono whitespace-pre-wrap break-all leading-relaxed transition-all duration-200 select-none"
                                    :class="revealedFields.has(field) ? 'select-text' : 'blur-sm'"
                                >
                                    {{ decryptedFields[field] }}
                                </p>
                            </template>
                            <template v-else-if="PASSWORD_FIELDS.includes(field)">
                                <p class="text-sm text-primary font-mono break-all">
                                    {{ revealedFields.has(field) ? decryptedFields[field] : '••••••••' }}
                                </p>
                            </template>
                            <template v-else>
                                <p class="text-sm text-primary break-all">{{ decryptedFields[field] }}</p>
                            </template>
                        </div>

                        <div class="flex items-center gap-0.5 shrink-0 mt-0.5">
                            <AppIconButton
                                v-if="PASSWORD_FIELDS.includes(field) || TEXTAREA_FIELDS.includes(field)"
                                color="ghost"
                                :title="revealedFields.has(field) ? t('vault.view.hide') : t('vault.view.reveal')"
                                v-on:click="toggleReveal(field)"
                            >
                                <component :is="revealedFields.has(field) ? EyeOff : Eye" class="w-4 h-4" :stroke-width="1.5" />
                            </AppIconButton>
                            <AppIconButton
                                color="accent"
                                :title="t('vault.view.copy')"
                                v-on:click="copy(field, decryptedFields[field])"
                            >
                                <Copy class="w-4 h-4" :stroke-width="1.5" />
                            </AppIconButton>
                        </div>
                    </div>

                    <div v-if="!visibleFields.length" class="py-6 text-center text-sm text-muted">
                        {{ t('vault.view.noFields') }}
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="close"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.close") }}</AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
