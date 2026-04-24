<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import AppButton from "@/components/AppButton.vue";

const props = defineProps({
    page: { type: Number, required: true },
    totalPages: { type: Number, required: true },
});

const emit = defineEmits(["change"]);

const { t } = useI18n();

const showNumbers = computed(() => props.totalPages <= 10);

function go(newPage) {
    if (newPage >= 1 && newPage <= props.totalPages) {
        emit("change", newPage);
    }
}
</script>

<template>
    <div v-if="totalPages > 1" class="flex items-center justify-between gap-4 text-sm">
        <span class="text-secondary shrink-0">
            {{ t("common.pagination", { page, totalPages }) }}
        </span>

        <div class="flex items-center gap-1">
            <AppButton variant="ghost" size="sm" :disabled="page <= 1" v-on:click="go(page - 1)">
                {{ t("pagination.previous") }}
            </AppButton>

            <template v-if="showNumbers">
                <button
                    v-for="pageNum in totalPages"
                    :key="pageNum"
                    type="button"
                    class="w-8 h-8 rounded-lg text-sm font-medium transition-colors"
                    :class="pageNum === page ? 'bg-indigo-600 text-white shadow-sm' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                    v-on:click="go(pageNum)"
                >
                    {{ pageNum }}
                </button>
            </template>

            <AppButton variant="ghost" size="sm" :disabled="page >= totalPages" v-on:click="go(page + 1)">
                {{ t("pagination.next") }}
            </AppButton>
        </div>
    </div>
</template>
