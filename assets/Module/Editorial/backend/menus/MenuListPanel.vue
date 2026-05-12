<script setup>
import { useI18n } from "vue-i18n";
import { Menu as MenuIcon } from "lucide-vue-next";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppButton from "@/shared/components/action/AppButton.vue";

const { t } = useI18n();

defineProps({
    menus: { type: Array, required: true },
    selectedId: { type: Number, default: null },
});

defineEmits(["select"]);
</script>

<template>
    <aside class="bg-surface border border-line rounded-xl p-3 space-y-3 self-start">
        <h2 class="text-sm font-semibold text-primary uppercase tracking-wide">{{ t("backend.menus.title") }}</h2>

        <AppNoData v-if="!menus.length" :message="t('backend.menus.empty')" />

        <ul v-else class="space-y-1">
            <li v-for="menu in menus" :key="menu.id">
                <AppButton
                    variant="ghost"
                    size="none"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-left text-sm transition-colors"
                    :class="selectedId === menu.id
                        ? 'bg-accent-600 text-white hover:bg-accent-700'
                        : 'hover:bg-surface-2 text-primary'"
                    v-on:click="$emit('select', menu)"
                >
                    <MenuIcon class="w-4 h-4 shrink-0" :stroke-width="2" />
                    <div class="flex-1 min-w-0">
                        <p class="font-medium truncate">{{ menu.name }}</p>
                        <p class="text-xs opacity-70 font-mono truncate">{{ menu.location }}</p>
                    </div>
                    <AppBadge :color="selectedId === menu.id ? 'gray' : 'accent'" class="shrink-0">
                        {{ menu.itemCount }}
                    </AppBadge>
                </AppButton>
            </li>
        </ul>
    </aside>
</template>
