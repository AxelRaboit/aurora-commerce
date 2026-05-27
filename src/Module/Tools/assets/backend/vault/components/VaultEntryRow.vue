<script setup>
import { useI18n } from 'vue-i18n';
import AppIconButton from '@shared/components/action/AppIconButton.vue';
import { ICONS } from '@tools/backend/vault/utils/recordTypes.js';
import { Star, Eye, Pencil, Trash2 } from 'lucide-vue-next';

defineProps({
    entry: { type: Object, required: true },
    showFolder: { type: Boolean, default: true },
});

const emit = defineEmits(['view', 'edit', 'delete', 'toggle-favorite', 'dragstart']);
const { t } = useI18n();
</script>

<template>
    <div
        class="flex items-center gap-3 px-4 py-3 rounded-lg border border-line bg-surface hover:bg-surface-2 group transition-colors cursor-grab active:cursor-grabbing"
        draggable="true"
        v-on:dragstart="emit('dragstart', $event)"
    >
        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-surface-2 border border-line shrink-0">
            <component :is="ICONS[entry.type] ?? ICONS['key-round']" class="w-4 h-4 text-secondary" :stroke-width="1.5" />
        </div>

        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-primary truncate">{{ entry.title }}</p>
            <p v-if="entry.url" class="text-xs text-muted truncate">{{ entry.url }}</p>
            <p v-else-if="showFolder && entry.folderName" class="text-xs text-muted truncate flex items-center gap-1">
                <span
                    v-if="entry.folderColor"
                    class="w-1.5 h-1.5 rounded-full inline-block"
                    :style="{ backgroundColor: entry.folderColor }"
                />
                {{ entry.folderName }}
            </p>
        </div>

        <slot name="extra-cells" />

        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
            <AppIconButton
                :color="entry.isFavorite ? 'amber' : 'ghost'"
                :title="t('vault.entries.favorite')"
                v-on:click="emit('toggle-favorite')"
            >
                <Star class="w-4 h-4" :stroke-width="1.5" :fill="entry.isFavorite ? 'currentColor' : 'none'" />
            </AppIconButton>
            <AppIconButton color="sky" :title="t('shared.common.view')" v-on:click="emit('view')">
                <Eye class="w-4 h-4" :stroke-width="1.5" />
            </AppIconButton>
            <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="emit('edit')">
                <Pencil class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="emit('delete')">
                <Trash2 class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
        </div>
    </div>
</template>
