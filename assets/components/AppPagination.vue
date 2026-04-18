<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    page:       { type: Number, required: true },
    totalPages: { type: Number, required: true },
    total:      { type: Number, required: true },
    perPage:    { type: Number, default: 20 },
    urlFn:      { type: Function, required: true },
});

const { t: translate } = useI18n({ useScope: 'global' });

const from = computed(() => props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1);
const lastItemNumber = computed(() => Math.min(props.page * props.perPage, props.total));

// Build page number list with ellipsis (…) as null
const pageNumbers = computed(() => {
    const total = props.totalPages;
    const current = props.page;
    if (total <= 7) {
        return Array.from({ length: total }, (_, index) => index + 1);
    }
    const pages = [];
    pages.push(1);
    if (current > 3) pages.push(null);
    for (let pageIndex = Math.max(2, current - 1); pageIndex <= Math.min(total - 1, current + 1); pageIndex++) {
        pages.push(pageIndex);
    }
    if (current < total - 2) pages.push(null);
    pages.push(total);
    return pages;
});
</script>

<template>
    <div v-if="totalPages > 1" class="mt-4 space-y-3 flex flex-col items-center">
        <p class="text-sm text-secondary">
            {{ translate('pagination.results', { from, to: lastItemNumber, total }) }}
        </p>

        <div class="flex flex-wrap gap-1 items-center justify-center">
            <a
                :href="page > 1 ? urlFn(page - 1) : undefined"
                class="px-3 py-1 rounded text-sm transition inline-flex items-center gap-1"
                :class="page > 1 ? 'bg-surface-2 text-secondary hover:bg-surface-3' : 'bg-surface-2/50 text-subtle cursor-not-allowed pointer-events-none'"
            >
                <ChevronLeft class="w-3.5 h-3.5" :stroke-width="2" />
                {{ translate('pagination.previous') }}
            </a>

            <template v-for="(pageNumber, index) in pageNumbers" :key="index">
                <a
                    v-if="pageNumber !== null"
                    :href="urlFn(pageNumber)"
                    class="px-3 py-1 rounded text-sm transition"
                    :class="pageNumber === page ? 'bg-violet-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                >
                    {{ pageNumber }}
                </a>
                <span v-else class="px-1 text-sm text-subtle">…</span>
            </template>

            <a
                :href="page < totalPages ? urlFn(page + 1) : undefined"
                class="px-3 py-1 rounded text-sm transition inline-flex items-center gap-1"
                :class="page < totalPages ? 'bg-surface-2 text-secondary hover:bg-surface-3' : 'bg-surface-2/50 text-subtle cursor-not-allowed pointer-events-none'"
            >
                {{ translate('pagination.next') }}
                <ChevronRight class="w-3.5 h-3.5" :stroke-width="2" />
            </a>
        </div>
    </div>
</template>
