<script setup>
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link2, FileSearch, X, FileText } from 'lucide-vue-next';
import AppIconButton from '@shared/components/action/AppIconButton.vue';
import AppNoData from '@shared/components/feedback/AppNoData.vue';

const props = defineProps({
    noteId: { type: Number, default: null },
    // (id) => Promise<{ok, payload}>
    fetchBacklinks: { type: Function, required: true },
    fetchUnlinkedMentions: { type: Function, required: true },
});

const emit = defineEmits(['close', 'navigate']);

const { t } = useI18n();

const tab = ref('backlinks');
const backlinks = ref([]);
const mentions = ref([]);
const loading = ref(false);

const items = computed(() => (tab.value === 'backlinks' ? backlinks.value : mentions.value));

watch(
    () => props.noteId,
    async (id) => {
        if (id === null) {
            backlinks.value = [];
            mentions.value = [];
            return;
        }
        await refresh();
    },
    { immediate: true },
);

watch(tab, async () => {
    await refresh();
});

async function refresh() {
    if (props.noteId === null) return;
    loading.value = true;
    try {
        if (tab.value === 'backlinks') {
            const { ok, payload } = await props.fetchBacklinks(props.noteId);
            backlinks.value = ok ? payload.backlinks ?? [] : [];
        } else {
            const { ok, payload } = await props.fetchUnlinkedMentions(props.noteId);
            mentions.value = ok ? payload.mentions ?? [] : [];
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <aside class="w-72 shrink-0 border-l border-line flex flex-col bg-surface-2/30">
        <header class="p-3 border-b border-line flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-primary">{{ t('notes.markdown.links.title') }}</h3>
            <AppIconButton
                :title="t('notes.markdown.links.close')"
                size="sm"
                variant="ghost"
                v-on:click="emit('close')"
            >
                <X class="w-4 h-4" :stroke-width="2" />
            </AppIconButton>
        </header>

        <div class="px-3 pt-2 flex gap-1 text-xs">
            <button
                type="button"
                class="flex-1 px-2 py-1.5 rounded-md flex items-center justify-center gap-1.5 transition-colors"
                :class="tab === 'backlinks'
                    ? 'bg-accent-100 dark:bg-accent-900/30 text-primary'
                    : 'text-muted hover:text-secondary hover:bg-surface'"
                v-on:click="tab = 'backlinks'"
            >
                <Link2 class="w-3.5 h-3.5" :stroke-width="2" />
                <span>{{ t('notes.markdown.links.backlinks') }}</span>
            </button>
            <button
                type="button"
                class="flex-1 px-2 py-1.5 rounded-md flex items-center justify-center gap-1.5 transition-colors"
                :class="tab === 'mentions'
                    ? 'bg-accent-100 dark:bg-accent-900/30 text-primary'
                    : 'text-muted hover:text-secondary hover:bg-surface'"
                v-on:click="tab = 'mentions'"
            >
                <FileSearch class="w-3.5 h-3.5" :stroke-width="2" />
                <span>{{ t('notes.markdown.links.mentions') }}</span>
            </button>
        </div>

        <div class="flex-1 overflow-auto p-2">
            <div v-if="loading" class="text-xs text-muted px-2 py-3">
                {{ t('notes.markdown.links.loading') }}
            </div>

            <ul v-else-if="items.length > 0" class="space-y-0.5">
                <li v-for="item in items" :key="item.id">
                    <button
                        type="button"
                        class="w-full text-left px-2 py-1.5 rounded-md text-sm text-secondary hover:bg-surface flex items-center gap-2"
                        v-on:click="emit('navigate', item.id)"
                    >
                        <FileText class="w-3.5 h-3.5 text-muted shrink-0" :stroke-width="1.75" />
                        <span class="truncate">
                            {{ item.title || t('notes.markdown.untitled') }}
                        </span>
                    </button>
                </li>
            </ul>

            <AppNoData
                v-else
                :title="tab === 'backlinks' ? t('notes.markdown.links.empty_backlinks') : t('notes.markdown.links.empty_mentions')"
                :description="tab === 'backlinks' ? t('notes.markdown.links.empty_backlinks_description') : t('notes.markdown.links.empty_mentions_description')"
                :icon="tab === 'backlinks' ? Link2 : FileSearch"
            />
        </div>
    </aside>
</template>
